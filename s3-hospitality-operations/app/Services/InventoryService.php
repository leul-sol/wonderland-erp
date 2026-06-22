<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
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

                $item = InventoryItem::query()->lockForUpdate()->find($ingredient->id);
                if ($item === null) {
                    throw new InvalidArgumentException('Inventory item '.$ingredient->id.' not found.');
                }

                if ((float) $item->quantity_on_hand < $requiredQty) {
                    throw new InvalidArgumentException('Insufficient stock for '.$item->name.'.');
                }

                $lineCost = round($requiredQty * (float) $item->unit_cost, 2);
                $totalCogs += $lineCost;

                $item->decrement('quantity_on_hand', $requiredQty);

                StockMovement::query()->create([
                    'inventory_item_id' => $item->id,
                    'movement_type' => 'sale',
                    'quantity' => -$requiredQty,
                    'unit_cost' => $item->unit_cost,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);
            }
        });

        return round($totalCogs, 2);
    }
}
