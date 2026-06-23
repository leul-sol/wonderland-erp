<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Models\InventoryItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query()->with('category')->orderBy('sku');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
        }

        return response()->json([
            'data' => $query->get()->map(fn ($item) => $this->inventoryItemPayload($item))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:30', 'unique:inventory_items,sku'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'rotation_strategy' => ['nullable', 'in:fifo,fefo'],
            'is_perishable' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $item = InventoryItem::query()->create($data + ['is_active' => true, 'quantity_on_hand' => 0]);

        return response()->json(['data' => $this->inventoryItemPayload($item)], 201);
    }

    public function show(InventoryItem $item): JsonResponse
    {
        $item->load('category', 'stockBatches');

        return response()->json(['data' => $this->inventoryItemPayload($item)]);
    }

    public function update(Request $request, InventoryItem $item): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'rotation_strategy' => ['nullable', 'in:fifo,fefo'],
            'is_perishable' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'gte:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item->update($data);

        return response()->json(['data' => $this->inventoryItemPayload($item->fresh())]);
    }

    public function stock(InventoryItem $item): JsonResponse
    {
        $batches = StockBatch::query()
            ->where('inventory_item_id', $item->id)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_date')
            ->get();

        return response()->json([
            'data' => [
                'item_id' => $item->id,
                'current_stock' => (string) $item->quantity_on_hand,
                'batches' => $batches,
            ],
        ]);
    }

    public function movements(InventoryItem $item, Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->where('inventory_item_id', $item->id)
            ->orderByDesc('id')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json(['data' => $movements]);
    }
}
