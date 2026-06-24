<?php

namespace App\Http\Controllers\Hr;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S1IdentityClient;
use App\Services\Api\S2WorkforceClient;
use App\Services\Auth\PortalAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S2WorkforceClient $s2,
        private readonly S1IdentityClient $s1,
        private readonly PortalAuthService $auth,
    ) {
    }

    public function index(): Response|RedirectResponse
    {
        try {
            $response = $this->s2->employees();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'dashboard');
        }

        return Inertia::render('Hr/Employees/Index', [
            'employees' => $response['data'] ?? [],
            'canCreate' => $this->auth->hasAnyPermission(['S2.workforce.employees.create']),
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        try {
            $departments = $this->s2->departments();
            $positions = $this->s2->positions();
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.employees.index');
        }

        return Inertia::render('Hr/Employees/Create', [
            'departments' => $departments['data'] ?? [],
            'positions' => $positions['data'] ?? [],
            'defaultHireDate' => now()->toDateString(),
        ]);
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

    public function show(int $employee): Response|RedirectResponse
    {
        try {
            $response = $this->s2->employee($employee);
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

        return Inertia::render('Hr/Employees/Show', [
            'employee' => $response['data'] ?? [],
            'platformUser' => $platformUser,
        ]);
    }
}
