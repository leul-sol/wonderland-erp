<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\MenuCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    use RespondsWithApiErrors;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => MenuCategory::query()->where('is_active', true)->orderBy('display_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = MenuCategory::query()->create($data + ['is_active' => true]);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, MenuCategory $menuCategory): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $menuCategory->update($data);

        return response()->json(['data' => $menuCategory->fresh()]);
    }
}
