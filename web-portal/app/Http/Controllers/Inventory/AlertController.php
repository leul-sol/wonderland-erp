<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $lowStock = $this->s3->lowStockAlerts();
            $expiry = $this->s3->expiryAlerts();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.items.index');
        }

        return Inertia::render('Inventory/Alerts/Index', [
            'lowStockAlerts' => $this->normalizeAlerts($lowStock['data'] ?? []),
            'expiryAlerts' => $this->normalizeExpiryAlerts($expiry['data'] ?? []),
        ]);
    }

    /**
     * @param  mixed  $data
     * @return list<array<string, mixed>>
     */
    private function normalizeAlerts(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        return array_values(array_map(fn (array $item): array => [
            'id' => $item['id'] ?? null,
            'sku' => $item['sku'] ?? '',
            'name' => $item['name'] ?? '',
            'quantity_on_hand' => (string) ($item['quantity_on_hand'] ?? '0'),
            'reorder_level' => (string) ($item['reorder_level'] ?? '0'),
            'unit' => $item['unit'] ?? '',
        ], $data));
    }

    /**
     * @param  mixed  $data
     * @return list<array<string, mixed>>
     */
    private function normalizeExpiryAlerts(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        return array_values(array_map(function (array $batch): array {
            $item = $batch['inventory_item'] ?? [];

            return [
                'id' => $batch['id'] ?? null,
                'batch_code' => $batch['batch_code'] ?? '',
                'inventory_item_id' => $batch['inventory_item_id'] ?? ($item['id'] ?? null),
                'sku' => $item['sku'] ?? '',
                'name' => $item['name'] ?? '',
                'quantity_remaining' => (string) ($batch['quantity_remaining'] ?? '0'),
                'expiry_date' => $batch['expiry_date'] ?? null,
            ];
        }, $data));
    }
}
