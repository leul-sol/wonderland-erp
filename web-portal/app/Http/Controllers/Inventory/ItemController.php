<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ItemController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->inventoryItems();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Inventory/Items/Index', [
            'items' => $response['data'] ?? [],
        ]);
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
}
