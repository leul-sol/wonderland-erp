<?php

namespace App\Http\Controllers\Hr;

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

class DepartmentController extends Controller
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
        return Inertia::render('Hr/Organization/Departments/Index', [
            'canWrite' => $this->auth->hasAnyPermission(['S2.hr.departments.write']),
            'pageLoad' => $this->deferPageLoad(function () {
                $departments = $this->s2->departments();
                $employees = $this->s2->employees('active');

                return [
                    'departments' => $departments['data'] ?? [],
                    'employees' => $employees['data'] ?? [],
                ];
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'head_employee_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->s2->createDepartment([
                'code' => $data['code'],
                'name' => $data['name'],
                'head_employee_id' => isset($data['head_employee_id']) ? (int) $data['head_employee_id'] : null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'Department created.');
    }

    public function edit(int $department): Response|RedirectResponse
    {
        try {
            $response = $this->s2->department($department);
            $employees = $this->s2->employees('active');
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'hr.departments.index');
        }

        return Inertia::render('Hr/Organization/Departments/Edit', [
            'department' => $response['data'] ?? [],
            'employees' => $employees['data'] ?? [],
        ]);
    }

    public function update(Request $request, int $department): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'head_employee_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->s2->updateDepartment($department, [
                'code' => $data['code'],
                'name' => $data['name'],
                'head_employee_id' => isset($data['head_employee_id']) ? (int) $data['head_employee_id'] : null,
            ]);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'Department updated.');
    }

    public function destroy(int $department): RedirectResponse
    {
        try {
            $this->s2->deleteDepartment($department);
        } catch (ApiException $e) {
            return $this->redirectApiError($e);
        }

        return redirect()
            ->route('hr.departments.index')
            ->with('success', 'Department deleted.');
    }
}
