<?php

namespace App\Http\Controllers;

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
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly EmployeeService $employees)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Employee::query()->with('department')->orderBy('employee_number');

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

    public function show(Employee $employee): JsonResponse
    {
        return response()->json(['data' => $this->employeePayload($employee)]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        try {
            $employee = $this->employees->update($employee, $request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->employeePayload($employee)]);
    }

    public function archive(Request $request, Employee $employee): JsonResponse
    {
        try {
            $employee = $this->employees->archive($employee, $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->employeePayload($employee)]);
    }
}
