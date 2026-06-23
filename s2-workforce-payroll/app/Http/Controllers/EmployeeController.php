<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesDepartmentScope;
use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EmployeeController extends Controller
{
    use AppliesDepartmentScope;
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly EmployeeService $employees)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeEmployeeQuery(
            Employee::query()->with('department')->orderBy('employee_number'),
            $request,
        );

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($e) => $this->employeePayload($e))->values(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employees->create($request->validated());

        return response()->json(['data' => $this->employeePayload($employee)], 201);
    }

    public function show(Request $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->employeePayload($employee)]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
            $employee = $this->employees->update($employee, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->employeePayload($employee)]);
    }

    public function archive(Request $request, Employee $employee): JsonResponse
    {
        try {
            $this->assertEmployeeInScope($employee, $request);
            $employee = $this->employees->archive($employee, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->departmentScopeError($e);
        }

        return response()->json(['data' => $this->employeePayload($employee)]);
    }
}
