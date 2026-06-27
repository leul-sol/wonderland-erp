<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FiscalPeriodController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Finance/FiscalPeriods/Index', [
            'canClose' => $this->auth->hasAnyPermission(['S4.finance.fiscal_periods.close']),
            'canLock' => $this->auth->hasAnyPermission(['S4.finance.fiscal_periods.lock']),
            'canCreate' => $this->auth->hasAnyPermission(['S4.finance.fiscal_periods.create']),
            'fiscalPeriods' => $this->deferApi(fn () => ($this->s4->fiscalPeriods())['data'] ?? []),
        ]);
    }

    public function openNext(): RedirectResponse
    {
        try {
            $periods = ($this->s4->fiscalPeriods())['data'] ?? [];
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $latest = collect($periods)->sortByDesc('end_date')->first();

        if (! is_array($latest)) {
            return back()->with('error', 'No fiscal periods exist to extend.');
        }

        $nextStart = \Carbon\Carbon::parse((string) $latest['end_date'])->addDay();
        $periodNumber = (int) ($latest['period_number'] ?? 0) + 1;
        $year = (int) ($latest['year'] ?? $nextStart->year);

        if ($periodNumber > 12) {
            $year++;
            $periodNumber = 1;
        }

        try {
            $this->s4->createFiscalPeriod([
                'year' => $year,
                'period_number' => $periodNumber,
                'start_date' => $nextStart->toDateString(),
                'end_date' => $nextStart->copy()->endOfMonth()->toDateString(),
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', "Opened fiscal period {$year}-P{$periodNumber}.");
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
