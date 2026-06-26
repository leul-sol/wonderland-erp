<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ValuationController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->stockValuation();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.items.index');
        }

        $data = $response['data'] ?? [];

        return Inertia::render('Inventory/Valuation/Index', [
            'totalValue' => (string) ($data['total_value'] ?? '0'),
            'lines' => $data['lines'] ?? [],
        ]);
    }
}
