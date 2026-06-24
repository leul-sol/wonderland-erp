<?php

namespace App\Http\Controllers\Consumption;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use App\Services\Api\S3HospitalityClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s3->consumptionPeriods();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        $employees = [];

        if ($this->auth->hasAnyPermission(['S2.workforce.employees.read'])) {
            try {
                $employeeResponse = $this->s2->employees('active');
                $employees = $employeeResponse['data'] ?? [];
            } catch (ApiException) {
                // Employee picker optional when S2 is unavailable.
            }
        }

        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->toDateString();

        return Inertia::render('Consumption/Periods/Index', [
            'periods' => $response['data'] ?? [],
            'employees' => is_array($employees) ? $employees : [],
            'canWrite' => $this->auth->hasAnyPermission(['S3.restaurant.consumption.write']),
            'defaultPeriodStart' => $monthStart,
            'defaultPeriodEnd' => $monthEnd,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'min:1'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        try {
            $this->s3->openConsumptionPeriod([
                'employee_id' => (int) $data['employee_id'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('consumption.periods.index')
            ->with('success', 'Consumption period opened.');
    }

    public function close(int $period): RedirectResponse
    {
        try {
            $this->s3->closeConsumptionPeriod($period);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('consumption.periods.index')
            ->with('success', 'Period closed and pushed to payroll deduction.');
    }

    public function createOrder(int $period): RedirectResponse
    {
        try {
            $response = $this->s3->createConsumptionOrder($period);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'consumption.periods.index');
        }

        $orderId = (int) ($response['data']['id'] ?? 0);

        if ($orderId <= 0) {
            return back()->with('error', 'Meal order was not created.');
        }

        return redirect()
            ->route('consumption.orders.show', $orderId)
            ->with('success', 'Meal order opened. Add items and finalize.');
    }
}
