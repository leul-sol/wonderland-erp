<?php

namespace App\Http\Controllers\Inventory;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function show(int $goodsReceipt): Response|RedirectResponse
    {
        try {
            $response = $this->s3->goodsReceipt($goodsReceipt);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'inventory.purchase-orders.index');
        }

        return Inertia::render('Inventory/GoodsReceipts/Show', [
            'goodsReceipt' => $response['data'] ?? [],
        ]);
    }
}
