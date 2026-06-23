<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function __construct(private readonly FifoFefoService $fifoFefo)
    {
    }

    public function adjust(int $itemId, float $quantity, string $reason, int $createdBy = 0): StockMovement
    {
        if ($quantity == 0.0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return DB::transaction(function () use ($itemId, $quantity, $reason, $createdBy) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($itemId);

            if ($quantity > 0) {
                $item->increment('quantity_on_hand', $quantity);
                StockBatch::query()->create([
                    'inventory_item_id' => $item->id,
                    'batch_code' => 'ADJ-'.now()->format('YmdHis'),
                    'quantity_received' => $quantity,
                    'quantity_remaining' => $quantity,
                    'unit_cost' => $item->unit_cost,
                    'received_date' => now()->toDateString(),
                    'status' => 'active',
                ]);
            } else {
                $this->fifoFefo->dispatch($item, abs($quantity), 'adjustment', 0);
            }

            return StockMovement::query()->create([
                'inventory_item_id' => $item->id,
                'movement_type' => 'adjustment',
                'quantity' => $quantity,
                'unit_cost' => $item->unit_cost,
                'reference_type' => 'adjustment',
                'reference_id' => 0,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
        });
    }

    public function writeOff(int $itemId, float $quantity, int $createdBy = 0): StockMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Write-off quantity must be positive.');
        }

        return DB::transaction(function () use ($itemId, $quantity, $createdBy) {
            $item = InventoryItem::query()->findOrFail($itemId);
            $this->fifoFefo->dispatch($item, $quantity, 'write_off', 0);

            StockBatch::query()
                ->where('inventory_item_id', $itemId)
                ->where('status', 'active')
                ->where('quantity_remaining', '<=', 0)
                ->update(['status' => 'expired']);

            return StockMovement::query()->create([
                'inventory_item_id' => $itemId,
                'movement_type' => 'write_off',
                'quantity' => -$quantity,
                'unit_cost' => $item->unit_cost,
                'reference_type' => 'write_off',
                'reference_id' => 0,
                'created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
        });
    }

    /**
     * @return Collection<int, StockBatch>
     */
    public function expiryAlerts(): Collection
    {
        $days = (int) config('hospitality.expiry_alert_days', 14);

        return StockBatch::query()
            ->with('inventoryItem')
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    public function lowStockAlerts(): Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('sku')
            ->get();
    }

    /**
     * @return array{total_value: float, lines: list<array{item_id: int, sku: string, quantity: float, value: float}>}
     */
    public function valuation(): array
    {
        $lines = [];
        $total = 0.0;

        $batches = StockBatch::query()
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->with('inventoryItem')
            ->get();

        foreach ($batches as $batch) {
            $value = round((float) $batch->quantity_remaining * (float) $batch->unit_cost, 2);
            $total += $value;
            $lines[] = [
                'item_id' => $batch->inventory_item_id,
                'sku' => $batch->inventoryItem?->sku ?? '',
                'batch_id' => $batch->id,
                'quantity' => (float) $batch->quantity_remaining,
                'value' => $value,
            ];
        }

        return ['total_value' => round($total, 2), 'lines' => $lines];
    }
}
