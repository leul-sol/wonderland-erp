<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiDashboardController extends Controller
{
    use DefersGatewayPageData;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function executive(Request $request): Response
    {
        return $this->render('executive', $request);
    }

    public function operations(Request $request): Response
    {
        return $this->render('operations', $request);
    }

    public function hotel(Request $request): Response
    {
        return $this->render('hotel', $request);
    }

    public function restaurant(Request $request): Response
    {
        return $this->render('restaurant', $request);
    }

    public function finance(Request $request): Response
    {
        return $this->render('finance', $request);
    }

    private function render(string $tab, Request $request): Response
    {
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        return Inertia::render('Finance/Dashboard/Index', [
            'tab' => $tab,
            'filters' => $query,
            'dashboard' => $this->deferPageLoad(function () use ($tab, $query) {
                $response = match ($tab) {
                    'executive' => $this->s4->executiveDashboard($query),
                    'operations' => $this->s4->operationsDashboard($query),
                    'hotel' => $this->s4->hotelDashboard(),
                    'restaurant' => $this->s4->restaurantDashboard(),
                    'finance' => $this->s4->financeDashboard($query),
                    default => $this->s4->executiveDashboard($query),
                };

                return $response['data'] ?? [];
            }),
        ]);
    }
}
