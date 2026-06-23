<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(private readonly FifoFefoService $fifoFefo)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data): InventoryItem
    {
        return InventoryItem::query()->create([
            'category_id' => $data['category_id'] ?? null,
            'sku' => $data['sku'],
            'name' => $data['name'],
            'unit' => $data['unit'] ?? 'ea',
            'rotation_strategy' => $data['rotation_strategy'] ?? 'fifo',
            'is_perishable' => $data['is_perishable'] ?? false,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(InventoryItem $item, array $data): InventoryItem
    {
        $item->update([
            'category_id' => $data['category_id'] ?? $item->category_id,
            'sku' => $data['sku'] ?? $item->sku,
            'name' => $data['name'] ?? $item->name,
            'unit' => $data['unit'] ?? $item->unit,
            'rotation_strategy' => $data['rotation_strategy'] ?? $item->rotation_strategy,
            'is_perishable' => $data['is_perishable'] ?? $item->is_perishable,
            'unit_cost' => $data['unit_cost'] ?? $item->unit_cost,
            'reorder_level' => $data['reorder_level'] ?? $item->reorder_level,
            'is_active' => $data['is_active'] ?? $item->is_active,
        ]);

        return $item->fresh();
    }

    public function adjustStock(int $inventoryItemId, float $quantityDelta, string $reason, int $userId = 0): StockMovement
    {
        if ($quantityDelta == 0.0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        $item = InventoryItem::query()->findOrFail($inventoryItemId);

        return DB::transaction(function () use ($item, $quantityDelta, $reason, $userId) {
            if ($quantityDelta < 0) {
                $this->fifoFefo->dispatch($item, abs($quantityDelta), 'stock_adjustment', 0);
            } else {
                $item->increment('quantity_on_hand', $quantityDelta);
            }

            return StockMovement::query()->create([
                'inventory_item_id' => $item->id,
                'movement_type' => 'adjustment',
                'quantity' => $quantityDelta,
                'unit_cost' => $item->unit_cost,
                'reference_type' => 'stock_adjustment',
                'reference_id' => 0,
                'created_by' => $userId > 0 ? $userId : null,
            ]);
        });
    }

    public function writeOff(int $inventoryItemId, float $quantity, string $reason, int $userId = 0): StockMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Write-off quantity must be positive.');
        }

        $item = InventoryItem::query()->findOrFail($inventoryItemId);

        return DB::transaction(function () use ($item, $quantity, $reason, $userId) {
            $this->fifoFefo->dispatch($item, $quantity, 'write_off', 0);

            return StockMovement::query()->create([
                'inventory_item_id' => $item->id,
                'movement_type' => 'write_off',
                'quantity' => -$quantity,
                'unit_cost' => $item->unit_cost,
                'reference_type' => 'write_off',
                'reference_id' => 0,
                'created_by' => $userId > 0 ? $userId : null,
            ]);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function expiryAlerts(int $withinDays = 30): \Illuminate\Support\Collection
    {
        $cutoff = now()->addDays($withinDays)->toDateString();

        return \App\Models\StockBatch::query()
            ->with('inventoryItem')
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $cutoff)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($batch) => [
                'batch_id' => $batch->id,
                'inventory_item_id' => $batch->inventory_item_id,
                'sku' => $batch->inventoryItem?->sku,
                'name' => $batch->inventoryItem?->name,
                'batch_code' => $batch->batch_code,
                'quantity_remaining' => (string) $batch->quantity_remaining,
                'expiry_date' => $batch->expiry_date?->toDateString(),
            ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function lowStockAlerts(): \Illuminate\Support\Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->orderBy('sku')
            ->get()
            ->map(fn ($item) => [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity_on_hand' => (string) $item->quantity_on_hand,
                'reorder_level' => (string) $item->reorder_level,
            ]);
    }

    /**
     * @return array{total_value: string, items: list<array<string, mixed>>}
     */
    public function valuation(): array
    {
        $items = InventoryItem::query()->where('is_active', true)->orderBy('sku')->get();
        $total = 0.0;
        $rows = [];

        foreach ($items as $item) {
            $value = round((float) $item->quantity_on_hand * (float) $item->unit_cost, 2);
            $total += $value;
            $rows[] = [
                'inventory_item_id' => $item->id,
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity_on_hand' => (string) $item->quantity_on_hand,
                'unit_cost' => (string) $item->unit_cost,
                'value' => number_format($value, 2, '.', ''),
            ];
        }

        return [
            'total_value' => number_format($total, 2, '.', ''),
            'items' => $rows,
        ];
    }

    public function receiveStock(int $inventoryItemId, float $quantity, float $unitCost, string $referenceType, int $referenceId): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Receipt quantity must be positive.');
        }

        $item = InventoryItem::query()->findOrFail($inventoryItemId);

        DB::transaction(function () use ($item, $quantity, $unitCost, $referenceType, $referenceId) {
            $item->increment('quantity_on_hand', $quantity);
            $item->update(['unit_cost' => $unitCost]);

            StockMovement::query()->create([
                'inventory_item_id' => $item->id,
                'movement_type' => 'receipt',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);
        });
    }

    public function consumeForMenuItem(MenuItem $menuItem, int $orderQuantity, string $referenceType, int $referenceId): float
    {
        if ($orderQuantity <= 0) {
            return 0.0;
        }

        $menuItem->loadMissing('ingredients');
        $totalCogs = 0.0;

        DB::transaction(function () use ($menuItem, $orderQuantity, $referenceType, $referenceId, &$totalCogs) {
            foreach ($menuItem->ingredients as $ingredient) {
                $requiredQty = round((float) $ingredient->pivot->quantity * $orderQuantity, 3);
                if ($requiredQty <= 0) {
                    continue;
                }

                $item = InventoryItem::query()->find($ingredient->id);
                if ($item === null) {
                    throw new InvalidArgumentException('Inventory item '.$ingredient->id.' not found.');
                }

                $dispatched = $this->fifoFefo->dispatch($item, $requiredQty, $referenceType, $referenceId);

                foreach ($dispatched as $batch) {
                    $totalCogs += round($batch['quantity_taken'] * $batch['unit_cost'], 2);
                }
            }
        });

        return round($totalCogs, 2);
    }
}
