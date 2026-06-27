<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Controller;
use App\Services\Api\S3HospitalityClient;
use Inertia\Inertia;
use Inertia\Response;

class ValuationController extends Controller
{
    use DefersGatewayPageData;

    public function __construct(
        private readonly S3HospitalityClient $s3,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Inventory/Valuation/Index', [
            'pageLoad' => $this->deferPageLoad(function () {
                $response = $this->s3->stockValuation();
                $data = $response['data'] ?? [];

                return [
                    'totalValue' => (string) ($data['total_value'] ?? '0'),
                    'lines' => $data['lines'] ?? [],
                ];
            }),
        ]);
    }
}
