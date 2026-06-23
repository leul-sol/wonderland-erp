<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoodsReceiptService
{
    public function __construct(private readonly OutboxService $outbox)
    {
    }

    public function receive(PurchaseOrder $po, int $receivedBy): GoodsReceipt
    {
        if (! in_array($po->status, ['approved', 'partially_received'], true)) {
            throw new InvalidArgumentException('Only approved purchase orders can be received.');
        }

        $po->loadMissing(['lines.inventoryItem', 'supplier']);

        return DB::transaction(function () use ($po, $receivedBy) {
            $receipt = GoodsReceipt::query()->create([
                'purchase_order_id' => $po->id,
                'received_by' => $receivedBy > 0 ? $receivedBy : null,
                'received_at' => now(),
            ]);

            $receiptTotal = 0.0;
            $hasLines = false;

            foreach ($po->lines as $line) {
                $remaining = round((float) $line->quantity - (float) $line->quantity_received, 3);
                if ($remaining <= 0) {
                    continue;
                }

                $hasLines = true;
                $unitCost = (float) $line->unit_cost;
                $lineTotal = round($remaining * $unitCost, 2);
                $receiptTotal += $lineTotal;

                $receiptLine = GoodsReceiptLine::query()->create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_line_id' => $line->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'quantity_received' => $remaining,
                    'unit_cost' => $unitCost,
                ]);

                $item = InventoryItem::query()->lockForUpdate()->findOrFail($line->inventory_item_id);
                $item->increment('quantity_on_hand', $remaining);
                $item->update(['unit_cost' => $unitCost]);

                $receivedDate = now()->toDateString();
                StockBatch::query()->create([
                    'inventory_item_id' => $item->id,
                    'goods_receipt_line_id' => $receiptLine->id,
                    'batch_code' => 'GR-'.$receipt->id.'-'.$receiptLine->id,
                    'quantity_received' => $remaining,
                    'quantity_remaining' => $remaining,
                    'unit_cost' => $unitCost,
                    'received_date' => $receivedDate,
                    'expiry_date' => $item->is_perishable ? now()->addDays(30)->toDateString() : null,
                    'status' => 'active',
                ]);

                StockMovement::query()->create([
                    'inventory_item_id' => $item->id,
                    'movement_type' => 'receipt',
                    'quantity' => $remaining,
                    'unit_cost' => $unitCost,
                    'reference_type' => 'goods_receipt',
                    'reference_id' => $receipt->id,
                    'created_by' => $receivedBy > 0 ? $receivedBy : null,
                ]);

                $line->increment('quantity_received', $remaining);
            }

            if (! $hasLines) {
                throw new InvalidArgumentException('No remaining quantity to receive on this purchase order.');
            }

            if ($po->supplier_id !== null) {
                Supplier::query()
                    ->whereKey($po->supplier_id)
                    ->increment('outstanding_balance', round($receiptTotal, 2));
            }

            $po->refresh()->load('lines');
            $fullyReceived = $po->lines->every(
                fn ($line) => (float) $line->quantity_received >= (float) $line->quantity
            );

            $po->update([
                'status' => $fullyReceived ? 'closed' : 'partially_received',
                'received_at' => $fullyReceived ? now() : $po->received_at,
            ]);

            $this->outbox->enqueue(config('events.channels.goods_received'), [
                'goods_receipt_id' => $receipt->id,
                'purchase_order_id' => $po->id,
                'po_number' => $po->po_number,
                'receipt_total' => (string) round($receiptTotal, 2),
                'status' => $po->status,
            ]);

            return $receipt->fresh('lines');
        });
    }
}
