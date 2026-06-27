<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Concerns\LoadsGatewayDataInParallel;
use App\Http\Controllers\Controller;
use App\Services\Api\S1AdminClient;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;
    use LoadsGatewayDataInParallel;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly S1AdminClient $s1,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('Hr/Employees/Index', [
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.employees.create']),
            'defaultHireDate' => now()->toDateString(),
            'pageLoad' => $this->deferPageLoad(function () {
                $results = $this->fetchGatewayInParallel($this->s2, [
                    'employees' => ['path' => '/s2/api/v1/employees', 'query' => []],
                    'departments' => ['path' => '/s2/api/v1/departments', 'query' => []],
                    'positions' => ['path' => '/s2/api/v1/positions', 'query' => []],
                ]);
                $response = $this->requireParallelResult($results, 'employees');
                $departments = $results['departments'] ?? ['data' => []];
                $positions = $results['positions'] ?? ['data' => []];

                return [
                    'employees' => $response['data'] ?? [],
                    'departments' => $departments['data'] ?? [],
                    'positions' => $positions['data'] ?? [],
                ];
            }),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('hr.employees.index', ['open' => 'create']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'department_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'pension_category' => ['nullable', 'in:covered,not_covered'],
            'default_role' => ['nullable', 'string', 'max:50'],
            'hire_date' => ['nullable', 'date'],
        ]);

        try {
            $response = $this->s2->createEmployee([
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'department_id' => isset($data['department_id']) ? (int) $data['department_id'] : null,
                'position_id' => isset($data['position_id']) ? (int) $data['position_id'] : null,
                'job_title' => $data['job_title'] ?? null,
                'base_salary' => (float) $data['base_salary'],
                'pension_category' => $data['pension_category'] ?? 'covered',
                'default_role' => $data['default_role'] ?? 'report_viewer',
                'hire_date' => $data['hire_date'] ?? now()->toDateString(),
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        $employeeId = (int) ($response['data']['id'] ?? 0);

        if ($employeeId <= 0) {
            return back()->with('error', 'Employee was not created.');
        }

        return redirect()
            ->route('hr.employees.show', $employeeId)
            ->with('success', 'Employee created. Platform user provisioning may take a moment.');
    }

    public function show(Request $request, int $employee): Response|RedirectResponse
    {
        $tab = (string) $request->query('tab', 'profile');
        if (! in_array($tab, ['profile', 'leave', 'disciplinary', 'assets', 'guarantors', 'loans', 'payslips', 'platform'], true)) {
            $tab = 'profile';
        }

        try {
            $response = $this->s2->employee($employee);
            $hub = $this->loadEmployeeHubData($employee, $tab);
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.employees.index');
        }

        $platformUser = null;

        if ($this->auth->hasAnyPermission(['S1.identity.users.read', 'S1.admin.users.read'])) {
            try {
                $users = $this->s1->usersByEmployeeId($employee);
                $platformUser = ($users['data'][0] ?? null);
            } catch (ApiException) {
                // User may not be provisioned yet.
            }
        }

        $assetTypes = [];
        if ($tab === 'assets' && $this->auth->hasAnyPermission(['S2.hr.assets.write'])) {
            try {
                $assetTypes = $this->s2->assetTypes()['data'] ?? [];
            } catch (ApiException) {
                $hub['tabLoadError'] ??= 'Could not load asset types catalog.';
            }
        }

        return Inertia::render('Hr/Employees/Show', [
            'employee' => $response['data'] ?? [],
            'platformUser' => $platformUser,
            'activeTab' => $tab,
            'tabLoadError' => $hub['tabLoadError'],
            'leaveBalances' => $hub['leaveBalances'],
            'employeeLeaveRequests' => $hub['employeeLeaveRequests'],
            'disciplinaryRecords' => $hub['disciplinaryRecords'],
            'employeeAssets' => $hub['employeeAssets'],
            'guarantors' => $hub['guarantors'],
            'loans' => $hub['loans'],
            'payslipRuns' => $hub['payslipRuns'],
            'assetTypes' => $assetTypes,
            'canUpdate' => $this->auth->hasAnyPermission(['S2.workforce.employees.update']),
            'canViewLeave' => $this->auth->hasAnyPermission(['S2.workforce.leave_balances.read', 'S2.workforce.leave_requests.read']),
            'canWriteDisciplinary' => $this->auth->hasAnyPermission(['S2.hr.disciplinary.write']),
            'canReadDisciplinary' => $this->auth->hasAnyPermission(['S2.hr.disciplinary.read']),
            'canWriteAssets' => $this->auth->hasAnyPermission(['S2.hr.assets.write']),
            'canReadAssets' => $this->auth->hasAnyPermission(['S2.hr.assets.read']),
            'canWriteGuarantors' => $this->auth->hasAnyPermission(['S2.hr.guarantors.write']),
            'canReadGuarantors' => $this->auth->hasAnyPermission(['S2.hr.guarantors.read']),
            'canWriteLoans' => $this->auth->hasAnyPermission(['S2.workforce.loans.create']),
            'canReadLoans' => $this->auth->hasAnyPermission(['S2.workforce.loans.read']),
            'canReadPayslips' => $this->auth->hasAnyPermission(['S2.payroll.payslips.read']),
        ]);
    }

    public function edit(int $employee): Response|RedirectResponse
    {
        try {
            $response = $this->s2->employee($employee);
            $departments = $this->s2->departments();
            $positions = $this->s2->positions();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.employees.index');
        }

        return Inertia::render('Hr/Employees/Edit', [
            'employee' => $response['data'] ?? [],
            'departments' => $departments['data'] ?? [],
            'positions' => $positions['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $employee): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'department_id' => ['nullable', 'integer'],
            'position_id' => ['nullable', 'integer'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'pension_category' => ['nullable', 'in:covered,not_covered'],
            'default_role' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $this->s2->updateEmployee($employee, [
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'department_id' => isset($data['department_id']) ? (int) $data['department_id'] : null,
                'position_id' => isset($data['position_id']) ? (int) $data['position_id'] : null,
                'job_title' => $data['job_title'] ?? null,
                'base_salary' => (float) $data['base_salary'],
                'pension_category' => $data['pension_category'] ?? 'covered',
                'default_role' => $data['default_role'] ?? null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    /**
     * @return array{
     *     leaveBalances: list<mixed>,
     *     employeeLeaveRequests: list<mixed>,
     *     disciplinaryRecords: list<mixed>,
     *     employeeAssets: list<mixed>,
     *     guarantors: list<mixed>,
     *     loans: list<mixed>,
     *     payslipRuns: list<mixed>,
     *     tabLoadError: string|null
     * }
     */
    private function loadEmployeeHubData(int $employeeId, string $tab): array
    {
        $empty = [
            'leaveBalances' => [],
            'employeeLeaveRequests' => [],
            'disciplinaryRecords' => [],
            'employeeAssets' => [],
            'guarantors' => [],
            'loans' => [],
            'payslipRuns' => [],
            'tabLoadError' => null,
        ];

        $requests = [];

        if ($tab === 'leave') {
            if ($this->auth->hasAnyPermission(['S2.workforce.leave_balances.read'])) {
                $requests['leaveBalances'] = [
                    'path' => "s2/api/v1/employees/{$employeeId}/leave-balances",
                ];
            }

            if ($this->auth->hasAnyPermission(['S2.workforce.leave_requests.read'])) {
                $requests['employeeLeaveRequests'] = [
                    'path' => 's2/api/v1/leave-requests',
                    'query' => ['employee_id' => $employeeId],
                ];
            }
        }

        if ($tab === 'disciplinary' && $this->auth->hasAnyPermission(['S2.hr.disciplinary.read'])) {
            $requests['disciplinaryRecords'] = [
                'path' => "s2/api/v1/employees/{$employeeId}/disciplinary-records",
            ];
        }

        if ($tab === 'assets' && $this->auth->hasAnyPermission(['S2.hr.assets.read'])) {
            $requests['employeeAssets'] = [
                'path' => "s2/api/v1/employees/{$employeeId}/assets",
            ];
        }

        if ($tab === 'guarantors' && $this->auth->hasAnyPermission(['S2.hr.guarantors.read'])) {
            $requests['guarantors'] = [
                'path' => "s2/api/v1/employees/{$employeeId}/guarantors",
            ];
        }

        if ($tab === 'loans' && $this->auth->hasAnyPermission(['S2.workforce.loans.read'])) {
            $requests['loans'] = [
                'path' => "s2/api/v1/employees/{$employeeId}/loans",
            ];
        }

        if ($tab === 'payslips' && $this->auth->hasAnyPermission(['S2.payroll.payslips.read', 'S2.workforce.payroll_runs.read'])) {
            $requests['approvedRuns'] = [
                'path' => 's2/api/v1/payroll-runs',
                'query' => ['status' => 'approved'],
            ];
            $requests['lockedRuns'] = [
                'path' => 's2/api/v1/payroll-runs',
                'query' => ['status' => 'locked'],
            ];
        }

        if ($requests === []) {
            return $empty;
        }

        try {
            $results = $this->s2->fetchMany($requests);
        } catch (ApiException $e) {
            $empty['tabLoadError'] = $e->getMessage();

            return $empty;
        }

        $payslipRuns = collect($results['approvedRuns']['data'] ?? [])
            ->merge($results['lockedRuns']['data'] ?? [])
            ->filter(function (array $run) use ($employeeId): bool {
                foreach ($run['lines'] ?? [] as $line) {
                    if ((int) ($line['employee_id'] ?? 0) === $employeeId) {
                        return true;
                    }
                }

                return false;
            })
            ->unique('id')
            ->sortByDesc('id')
            ->values()
            ->all();

        $failedKeys = array_keys(array_filter($results, fn ($value) => $value === null));
        $tabLoadError = $failedKeys !== []
            ? 'Some workforce data could not be loaded. Try again or check S2: docker compose up -d s2-workforce'
            : null;

        return [
            'leaveBalances' => $results['leaveBalances']['data'] ?? [],
            'employeeLeaveRequests' => $results['employeeLeaveRequests']['data'] ?? [],
            'disciplinaryRecords' => $results['disciplinaryRecords']['data'] ?? [],
            'employeeAssets' => $results['employeeAssets']['data'] ?? [],
            'guarantors' => $results['guarantors']['data'] ?? [],
            'loans' => $results['loans']['data'] ?? [],
            'payslipRuns' => $payslipRuns,
            'tabLoadError' => $tabLoadError,
        ];
    }
}
