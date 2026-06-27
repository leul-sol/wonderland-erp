<?php

namespace App\Http\Controllers\Inventory;

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

class ItemController extends Controller
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
        return Inertia::render('Inventory/Items/Index', [
            'pageLoad' => $this->deferPageLoad(function () {
                $results = $this->fetchGatewayInParallel($this->s3, [
                    'items' => ['path' => '/s3/api/v1/items', 'query' => ['active_only' => false]],
                    'categories' => ['path' => '/s3/api/v1/item-categories', 'query' => []],
                ]);
                $response = $this->requireParallelResult($results, 'items');
                $categories = $results['categories'] ?? ['data' => []];

                return [
                    'items' => $response['data'] ?? [],
                    'categories' => $categories['data'] ?? [],
                ];
            }),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('inventory.items.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer'],
            'rotation_strategy' => ['nullable', 'in:fifo,fefo'],
            'is_perishable' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $payload = [
            'sku' => $data['sku'],
            'name' => $data['name'],
        ];

        foreach (['unit', 'unit_cost', 'category_id', 'rotation_strategy', 'is_perishable', 'reorder_level'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== '' && $data[$field] !== null) {
                $payload[$field] = $field === 'category_id' ? (int) $data[$field] : $data[$field];
            }
        }

        try {
            $response = $this->s3->createInventoryItem($payload);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $itemId = (int) ($response['data']['id'] ?? 0);

        return redirect()
            ->route('inventory.items.show', $itemId > 0 ? $itemId : 0)
            ->with('success', 'Inventory item created.');
    }

    public function show(int $item): Response|RedirectResponse
    {
        try {
            $itemResponse = $this->s3->inventoryItem($item);
            $stockResponse = $this->s3->inventoryItemStock($item);
            $movementsResponse = $this->s3->inventoryItemMovements($item);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.items.index');
        }

        $movements = $movementsResponse['data'] ?? [];
        if (isset($movements['data']) && is_array($movements['data'])) {
            $movements = $movements['data'];
        }

        return Inertia::render('Inventory/Items/Show', [
            'item' => $itemResponse['data'] ?? [],
            'stock' => $stockResponse['data'] ?? [],
            'movements' => is_array($movements) ? $movements : [],
        ]);
    }

    public function edit(int $item): Response|RedirectResponse
    {
        try {
            $itemResponse = $this->s3->inventoryItem($item);
            $categories = $this->s3->itemCategories();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.items.index');
        }

        return Inertia::render('Inventory/Items/Edit', [
            'item' => $itemResponse['data'] ?? [],
            'categories' => $categories['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'category_id' => ['nullable', 'integer'],
            'rotation_strategy' => ['nullable', 'in:fifo,fefo'],
            'is_perishable' => ['nullable', 'boolean'],
            'reorder_level' => ['nullable', 'numeric', 'gte:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->s3->updateInventoryItem($item, $data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('inventory.items.show', $item)
            ->with('success', 'Inventory item updated.');
    }

    public function adjust(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->s3->adjustStock([
                'inventory_item_id' => $item,
                'quantity' => (float) $data['quantity'],
                'reason' => $data['reason'] ?? 'Manual adjustment',
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Stock adjustment recorded.');
    }

    public function writeOff(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $this->s3->writeOffStock([
                'inventory_item_id' => $item,
                'quantity' => (float) $data['quantity'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Stock write-off recorded.');
    }
}
