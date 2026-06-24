<?php

namespace App\Http\Controllers\Payroll;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use App\Support\PayrollRunApprovalSteps;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRunController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s2->payrollRuns();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Payroll/Runs/Index', [
            'payrollRuns' => $response['data'] ?? [],
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.payroll_runs.create']),
        ]);
    }

    public function create(): Response
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        return Inertia::render('Payroll/Runs/Create', [
            'defaultPeriodStart' => $start,
            'defaultPeriodEnd' => $end,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        try {
            $response = $this->s2->createPayrollRun([
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $runId = (int) ($response['data']['id'] ?? 0);

        if ($runId <= 0) {
            return back()->with('error', 'Payroll run was not created.');
        }

        return redirect()
            ->route('payroll.runs.show', $runId)
            ->with('success', 'Payroll run created as draft.');
    }

    public function show(int $payrollRun): Response|RedirectResponse
    {
        try {
            $response = $this->s2->payrollRun($payrollRun);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'payroll.runs.index');
        }

        $run = $response['data'] ?? [];
        $status = (string) ($run['status'] ?? '');

        return Inertia::render('Payroll/Runs/Show', [
            'payrollRun' => $run,
            'approvalSteps' => PayrollRunApprovalSteps::steps(),
            'approvalCurrentStep' => PayrollRunApprovalSteps::currentStepKey($run),
            'canSubmit' => $status === 'draft' && $this->auth->hasAnyPermission(['S2.workforce.payroll_runs.create']),
            'canApprove' => $status === 'pending_approval' && $this->auth->hasAnyPermission(['S2.workforce.payroll_runs.approve']),
        ]);
    }

    public function submit(int $payrollRun): RedirectResponse
    {
        try {
            $this->s2->submitPayrollRun($payrollRun);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Payroll run submitted for approval.');
    }

    public function approve(int $payrollRun): RedirectResponse
    {
        try {
            $this->s2->approvePayrollRun($payrollRun, (string) Str::uuid());
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Payroll run approved and posted to finance.');
    }
}
