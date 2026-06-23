<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesHospitalityResources;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuItemController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesHospitalityResources;

    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::query()->with('ingredients')->orderBy('code');

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => $query->get()->map(fn ($item) => $this->menuItemPayload($item))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:menu_items,code'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'gte:0'],
            'employee_price' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer', 'exists:menu_categories,id'],
            'has_recipe' => ['nullable', 'boolean'],
        ]);

        $item = MenuItem::query()->create($data + ['is_active' => true]);

        return response()->json(['data' => $this->menuItemPayload($item)], 201);
    }

    public function show(MenuItem $menuItem): JsonResponse
    {
        $menuItem->load('ingredients', 'category');

        return response()->json(['data' => $this->menuItemPayload($menuItem)]);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'price' => ['sometimes', 'numeric', 'gte:0'],
            'employee_price' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer', 'exists:menu_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['is_available'])) {
            $data['is_active'] = (bool) $data['is_available'];
            unset($data['is_available']);
        }

        $menuItem->update($data);

        return response()->json(['data' => $this->menuItemPayload($menuItem->fresh('ingredients'))]);
    }

    public function updateRecipe(Request $request, MenuItem $menuItem): JsonResponse
    {
        $data = $request->validate([
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($menuItem, $data) {
            $sync = [];
            foreach ($data['ingredients'] as $ingredient) {
                $sync[(int) $ingredient['inventory_item_id']] = [
                    'quantity' => round((float) $ingredient['quantity'], 3),
                ];
            }

            $menuItem->ingredients()->sync($sync);
            $menuItem->update(['has_recipe' => true]);
        });

        return response()->json(['data' => $this->menuItemPayload($menuItem->fresh('ingredients'))]);
    }
}
