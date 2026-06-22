<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::query()->orderBy('sku');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => $query->get()->map(fn ($item) => $this->inventoryItemPayload($item))->values(),
        ]);
    }

    public function show(InventoryItem $item): JsonResponse
    {
        return response()->json(['data' => $this->inventoryItemPayload($item)]);
    }
}
