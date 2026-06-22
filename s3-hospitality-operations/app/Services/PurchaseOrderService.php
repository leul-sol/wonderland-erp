<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
    ) {
    }

    /**
     * @param  array<int, array{inventory_item_id: int, quantity: float|int, unit_cost: float|int}>  $lines
     */
    public function create(string $vendorName, array $lines): PurchaseOrder
    {
        if ($lines === []) {
            throw new InvalidArgumentException('Purchase order requires at least one line.');
        }

        return DB::transaction(function () use ($vendorName, $lines) {
            $po = PurchaseOrder::query()->create([
                'po_number' => $this->nextPoNumber(),
                'vendor_name' => $vendorName,
                'status' => 'draft',
                'total_amount' => 0,
            ]);

            $total = 0.0;

            foreach ($lines as $line) {
                $item = InventoryItem::query()->findOrFail((int) $line['inventory_item_id']);
                $quantity = round((float) $line['quantity'], 3);
                $unitCost = round((float) $line['unit_cost'], 2);
                $lineTotal = round($quantity * $unitCost, 2);
                $total += $lineTotal;

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);
            }

            $po->update(['total_amount' => round($total, 2)]);

            return $po->fresh('lines.inventoryItem');
        });
    }

    public function approve(PurchaseOrder $po, int $approvedBy): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new InvalidArgumentException('Only draft purchase orders can be approved.');
        }

        $po->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        $this->outbox->enqueue(config('events.channels.purchase_order_approved'), [
            'purchase_order_id' => $po->id,
            'po_number' => $po->po_number,
            'vendor_name' => $po->vendor_name,
            'total_amount' => (string) $po->total_amount,
        ]);

        return $po->fresh('lines.inventoryItem');
    }

    public function receive(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'approved') {
            throw new InvalidArgumentException('Only approved purchase orders can be received.');
        }

        $po->loadMissing('lines.inventoryItem');
        $accounts = config('hospitality.accounts');

        return DB::transaction(function () use ($po, $accounts) {
            foreach ($po->lines as $line) {
                $this->inventory->receiveStock(
                    $line->inventory_item_id,
                    (float) $line->quantity,
                    (float) $line->unit_cost,
                    'purchase_order',
                    $po->id
                );
            }

            $journal = $this->s4->postJournal([
                'description' => 'Goods received '.$po->po_number,
                'source_module' => 's3',
                'source_reference' => 'PO-'.$po->id,
                'lines' => [
                    ['account_code' => $accounts['inventory_fb'], 'debit' => (float) $po->total_amount, 'credit' => 0],
                    ['account_code' => $accounts['ap_suppliers'], 'debit' => 0, 'credit' => (float) $po->total_amount],
                ],
            ], 'po-'.$po->id.'-receive');

            $po->update([
                'status' => 'received',
                'received_at' => now(),
                's4_journal_entry_id' => (string) ($journal['data']['id'] ?? ''),
            ]);

            $this->outbox->enqueue(config('events.channels.goods_received'), [
                'purchase_order_id' => $po->id,
                'po_number' => $po->po_number,
                'total_amount' => (string) $po->total_amount,
                'journal_entry_id' => $po->s4_journal_entry_id,
            ]);

            return $po->fresh('lines.inventoryItem');
        });
    }

    private function nextPoNumber(): string
    {
        $last = PurchaseOrder::query()->orderByDesc('id')->lockForUpdate()->value('po_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/PO-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'PO-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
