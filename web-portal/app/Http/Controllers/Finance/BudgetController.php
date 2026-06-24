<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $variance = $this->s4->budgetVariance($query);
            $periods = $this->s4->fiscalPeriods();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Finance/Budget/Index', [
            'variance' => $variance['data'] ?? [],
            'fiscalPeriods' => $periods['data'] ?? [],
            'filters' => [
                'fiscal_period_id' => $request->input('fiscal_period_id'),
            ],
        ]);
    }
}
