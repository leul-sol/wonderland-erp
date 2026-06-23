<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FifoFefoService
{
    /**
     * @return list<array{batch_id: int, quantity_taken: float, unit_cost: float}>
     */
    public function dispatch(InventoryItem $item, float $quantity, string $referenceType, int $referenceId): array
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Dispatch quantity must be positive.');
        }

        return DB::transaction(function () use ($item, $quantity, $referenceType, $referenceId) {
            $item = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $query = StockBatch::query()
                ->where('inventory_item_id', $item->id)
                ->where('status', 'active')
                ->where('quantity_remaining', '>', 0)
                ->lockForUpdate();

            if ($item->rotation_strategy === 'fefo') {
                $query->orderByRaw('expiry_date IS NULL')
                    ->orderBy('expiry_date')
                    ->orderBy('received_date');
            } else {
                $query->orderBy('received_date');
            }

            $batches = $query->get();
            $remaining = $quantity;
            $taken = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $available = (float) $batch->quantity_remaining;
                if ($available <= 0) {
                    continue;
                }

                $take = min($available, $remaining);
                $newRemaining = round($available - $take, 3);

                $batch->update([
                    'quantity_remaining' => $newRemaining,
                    'status' => $newRemaining <= 0 ? 'depleted' : 'active',
                ]);

                StockMovement::query()->create([
                    'inventory_item_id' => $item->id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'dispatch',
                    'quantity' => -$take,
                    'unit_cost' => $batch->unit_cost,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);

                $taken[] = [
                    'batch_id' => $batch->id,
                    'quantity_taken' => $take,
                    'unit_cost' => (float) $batch->unit_cost,
                ];

                $remaining = round($remaining - $take, 3);
            }

            if ($remaining > 0.0005) {
                throw new InvalidArgumentException('Insufficient stock for '.$item->name.'.');
            }

            $item->decrement('quantity_on_hand', $quantity);

            return $taken;
        });
    }
}
