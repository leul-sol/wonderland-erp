<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\ItemCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    use RespondsWithApiErrors;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ItemCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $category = ItemCategory::query()->create($data + ['is_active' => true]);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, ItemCategory $itemCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $itemCategory->update($data);

        return response()->json(['data' => $itemCategory->fresh()]);
    }

    public function destroy(ItemCategory $itemCategory): JsonResponse
    {
        $itemCategory->update(['is_active' => false]);

        return response()->json(['data' => $itemCategory->fresh()]);
    }
}
