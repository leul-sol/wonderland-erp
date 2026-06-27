<?php

namespace App\Http\Controllers\Payroll;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeveranceController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Payroll/Severance/Index', [
            'canCalculate' => $this->auth->hasAnyPermission(['S2.workforce.severance.calculate']),
            'canPay' => $this->auth->hasAnyPermission(['S2.workforce.severance.pay']),
            'pageLoad' => $this->deferPageLoad(function () {
                $calculations = $this->s2->severanceCalculations();
                $employees = $this->s2->employees('active');

                return [
                    'calculations' => $calculations['data'] ?? [],
                    'employees' => $employees['data'] ?? [],
                ];
            }),
        ]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer'],
        ]);

        try {
            $this->s2->calculateSeverance((int) $data['employee_id']);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Severance calculated and liability posted.');
    }

    public function pay(int $severanceCalculation): RedirectResponse
    {
        try {
            $this->s2->paySeverance($severanceCalculation);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Severance payout recorded.');
    }
}
