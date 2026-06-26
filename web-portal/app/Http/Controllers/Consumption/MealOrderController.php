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

class MealOrderController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S3HospitalityClient $s3,
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function show(int $order): Response|RedirectResponse
    {
        try {
            $orderResponse = $this->s3->order($order);
            $menuResponse = $this->s3->menuItems();
            $orderData = $orderResponse['data'] ?? [];
            $periodId = (int) ($orderData['employee_consumption_period_id'] ?? 0);
            $periods = $periodId > 0 ? $this->s3->consumptionPeriods() : ['data' => []];
            $period = collect($periods['data'] ?? [])->firstWhere('id', $periodId);
            $employee = null;

            if ($period && $this->auth->hasAnyPermission(['S2.workforce.employees.read'])) {
                try {
                    $employeeResponse = $this->s2->employee((int) $period['employee_id']);
                    $employee = $employeeResponse['data'] ?? null;
                } catch (ApiException) {
                    // Employee name is optional when S2 is unavailable.
                }
            }
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'consumption.periods.index');
        }

        return Inertia::render('Consumption/MealOrders/Show', [
            'order' => $orderData,
            'period' => $period,
            'employee' => $employee,
            'menuItems' => $menuResponse['data'] ?? [],
        ]);
    }

    public function addLine(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'menu_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->s3->addOrderLine($order, [
                'menu_item_id' => (int) $data['menu_item_id'],
                'quantity' => (int) $data['quantity'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Item added to meal order.');
    }

    public function finalize(int $order): RedirectResponse
    {
        try {
            $response = $this->s3->finalizeOrder($order);
            $periodId = (int) ($response['data']['employee_consumption_period_id'] ?? 0);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        if ($periodId > 0) {
            return redirect()
                ->route('consumption.periods.index')
                ->with('success', 'Meal order finalized. Close the period when ready.');
        }

        return redirect()
            ->route('consumption.periods.index')
            ->with('success', 'Meal order finalized.');
    }
}
