<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use DefersGatewayPageData;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function index(Request $request): Response
    {
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        return Inertia::render('Finance/Budget/Index', [
            'filters' => [
                'fiscal_period_id' => $request->input('fiscal_period_id'),
            ],
            'variance' => $this->deferApi(fn () => ($this->s4->budgetVariance($query))['data'] ?? []),
            'fiscalPeriods' => $this->deferApi(fn () => ($this->s4->fiscalPeriods())['data'] ?? []),
        ]);
    }
}
