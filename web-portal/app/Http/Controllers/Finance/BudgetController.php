<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(Request $request): Response
    {
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $query = array_filter([
            'fiscal_period_id' => $fiscalPeriodId,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        $budgetQuery = $fiscalPeriodId ? ['fiscal_period_id' => $fiscalPeriodId] : [];

        return Inertia::render('Finance/Budget/Index', [
            'canCreate' => $this->auth->hasAnyPermission(['S4.finance.budgets.create']),
            'filters' => [
                'fiscal_period_id' => $fiscalPeriodId,
            ],
            'variance' => $this->deferApi(fn () => ($this->s4->budgetVariance($query))['data'] ?? []),
            'fiscalPeriods' => $this->deferApi(fn () => ($this->s4->fiscalPeriods())['data'] ?? []),
            'budgetLines' => $this->deferApi(fn () => ($this->s4->budgetLines($budgetQuery))['data'] ?? []),
            'accounts' => $this->deferApi(fn () => ($this->s4->accounts())['data'] ?? []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_period_id' => ['required', 'integer'],
            'account_code' => ['required', 'string', 'max:20'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $this->s4->createBudgetLine($data);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Budget line saved.');
    }
}
