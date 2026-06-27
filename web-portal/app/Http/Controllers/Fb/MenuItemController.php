<?php

namespace App\Http\Controllers\Fb;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\LoadsGatewayDataInParallel;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuItemController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use LoadsGatewayDataInParallel;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Fb/MenuItems/Index', [
            'pageLoad' => $this->deferPageLoad(function () {
                $results = $this->fetchGatewayInParallel($this->s3, [
                    'menuItems' => ['path' => '/s3/api/v1/menu-items', 'query' => ['active_only' => false]],
                    'categories' => ['path' => '/s3/api/v1/menu-categories', 'query' => ['active_only' => false]],
                ]);
                $response = $this->requireParallelResult($results, 'menuItems');
                $categories = $results['categories'] ?? ['data' => []];

                return [
                    'menuItems' => $response['data'] ?? [],
                    'categories' => $categories['data'] ?? [],
                ];
            }),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('fb.menu-items.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'gte:0'],
            'employee_price' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer'],
        ]);

        $payload = [
            'code' => $data['code'],
            'name' => $data['name'],
            'price' => (float) $data['price'],
        ];

        if (isset($data['employee_price']) && $data['employee_price'] !== '') {
            $payload['employee_price'] = (float) $data['employee_price'];
        }

        if (! empty($data['category_id'])) {
            $payload['category_id'] = (int) $data['category_id'];
        }

        try {
            $response = $this->s3->createMenuItem($payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $itemId = (int) ($response['data']['id'] ?? 0);

        if ($itemId <= 0) {
            return redirect()
                ->route('fb.menu-items.index')
                ->with('error', 'Menu item was not created.');
        }

        return redirect()
            ->route('fb.menu-items.edit', $itemId)
            ->with('success', 'Menu item created. Add a recipe if this item consumes inventory.');
    }

    public function edit(int $menuItem): Response|RedirectResponse
    {
        try {
            $itemResponse = $this->s3->menuItem($menuItem);
            $categories = $this->s3->menuCategories(false);
            $inventory = $this->s3->inventoryItems();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'fb.menu-items.index');
        }

        return Inertia::render('Fb/MenuItems/Edit', [
            'menuItem' => $itemResponse['data'] ?? [],
            'categories' => $categories['data'] ?? [],
            'inventoryItems' => $inventory['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $menuItem): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'gte:0'],
            'employee_price' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'price' => (float) $data['price'],
            'is_available' => (bool) ($data['is_available'] ?? true),
        ];

        if (array_key_exists('employee_price', $data) && $data['employee_price'] !== '' && $data['employee_price'] !== null) {
            $payload['employee_price'] = (float) $data['employee_price'];
        }

        if (! empty($data['category_id'])) {
            $payload['category_id'] = (int) $data['category_id'];
        } else {
            $payload['category_id'] = null;
        }

        try {
            $this->s3->updateMenuItem($menuItem, $payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Menu item updated.');
    }

    public function updateRecipe(Request $request, int $menuItem): RedirectResponse
    {
        $data = $request->validate([
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.inventory_item_id' => ['required', 'integer'],
            'ingredients.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $ingredients = collect($data['ingredients'])->map(fn (array $line) => [
            'inventory_item_id' => (int) $line['inventory_item_id'],
            'quantity' => (float) $line['quantity'],
        ])->values()->all();

        try {
            $this->s3->updateMenuItemRecipe($menuItem, ['ingredients' => $ingredients]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Recipe saved.');
    }
}
