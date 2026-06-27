<?php

namespace App\Http\Controllers\Consumption;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
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
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->toDateString();

        return Inertia::render('Consumption/Periods/Index', [
            'canWrite' => $this->auth->hasAnyPermission(['S3.restaurant.consumption.write']),
            'defaultPeriodStart' => $monthStart,
            'defaultPeriodEnd' => $monthEnd,
            'pageLoad' => $this->deferPageLoad(function () {
                $response = $this->s3->consumptionPeriods();

                $employees = [];

                if ($this->auth->hasAnyPermission(['S2.workforce.employees.read'])) {
                    try {
                        $employeeResponse = $this->s2->employees('active');
                        $employees = $employeeResponse['data'] ?? [];
                    } catch (ApiException) {
                        // Employee picker optional when S2 is unavailable.
                    }
                }

                $employees = is_array($employees) ? $employees : [];
                $employeeMap = collect($employees)
                    ->mapWithKeys(fn (array $employee): array => [
                        (int) $employee['id'] => $employee['full_name'] ?? $employee['employee_number'] ?? ('#'.$employee['id']),
                    ])
                    ->all();

                return [
                    'periods' => $response['data'] ?? [],
                    'employees' => $employees,
                    'employeeMap' => $employeeMap,
                ];
            }),
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
