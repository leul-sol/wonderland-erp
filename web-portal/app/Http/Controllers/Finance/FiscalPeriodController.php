<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FiscalPeriodController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s4->fiscalPeriods();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Finance/FiscalPeriods/Index', [
            'fiscalPeriods' => $response['data'] ?? [],
            'canClose' => $this->auth->hasAnyPermission(['S4.finance.fiscal_periods.close']),
            'canLock' => $this->auth->hasAnyPermission(['S4.finance.fiscal_periods.lock']),
        ]);
    }

    public function close(int $fiscalPeriod): RedirectResponse
    {
        try {
            $this->s4->closeFiscalPeriod($fiscalPeriod);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Fiscal period close step recorded.');
    }

    public function lock(int $fiscalPeriod): RedirectResponse
    {
        try {
            $this->s4->lockFiscalPeriod($fiscalPeriod);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Fiscal period locked.');
    }
}
