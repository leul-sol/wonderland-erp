<?php

namespace App\Http\Controllers\Payroll;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
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
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Payroll/Runs/Index', [
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.payroll_runs.create']),
            'defaultPeriodStart' => now()->startOfMonth()->toDateString(),
            'defaultPeriodEnd' => $this->defaultPayrollPeriodEnd(),
            'maxPeriodEnd' => $this->defaultPayrollPeriodEnd(),
            'canRecordAttendance' => $this->auth->hasAnyPermission(['S2.workforce.attendance.create']),
            'payrollRuns' => $this->deferApi(fn () => ($this->s2->payrollRuns())['data'] ?? []),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('payroll.runs.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        if ($data['period_end'] > now()->toDateString()) {
            return back()
                ->withInput()
                ->with('error', 'Period end cannot be in the future. Use today or the last completed weekday, and ensure weekday attendance exists for every active employee in the range.');
        }

        try {
            $response = $this->s2->createPayrollRun([
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
            ]);
        } catch (ApiException $e) {
            return $this->redirectPayrollCreateError($e);
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
            'canLock' => $status === 'approved' && $this->auth->hasAnyPermission(['S2.workforce.payroll_runs.approve']),
            'canReadPayslips' => in_array($status, ['approved', 'locked'], true)
                && $this->auth->hasAnyPermission(['S2.payroll.payslips.read']),
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

    public function lock(int $payrollRun): RedirectResponse
    {
        try {
            $this->s2->lockPayrollRun($payrollRun);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return back()->with('success', 'Payroll run locked. Payslips are final and the run cannot be changed.');
    }

    private function defaultPayrollPeriodEnd(): string
    {
        $today = now();

        if ($today->isWeekend()) {
            return $today->previousWeekday()->toDateString();
        }

        return $today->toDateString();
    }

    private function redirectPayrollCreateError(ApiException $exception): RedirectResponse
    {
        $redirect = back()->withInput()->with($this->flashApiError($exception));

        if ($exception->errorCode === 'VALIDATION_ERROR'
            && preg_match('/Missing attendance for (.+) on (\d{4}-\d{2}-\d{2})/', $exception->getMessage(), $matches) === 1) {
            $redirect->with('attendanceGap', [
                'employee_name' => $matches[1],
                'work_date' => $matches[2],
            ]);
        }

        return $redirect;
    }
}
