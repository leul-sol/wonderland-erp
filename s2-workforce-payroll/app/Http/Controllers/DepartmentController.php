<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class DepartmentController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(private readonly DepartmentService $departments)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Department::query()->orderBy('name')->get()
                ->map(fn ($d) => $this->departmentPayload($d))->values(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departments->create($request->validated());

        return response()->json(['data' => $this->departmentPayload($department)], 201);
    }

    public function show(Department $department): JsonResponse
    {
        return response()->json(['data' => $this->departmentPayload($department)]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $department = $this->departments->update($department, $request->validated());

        return response()->json(['data' => $this->departmentPayload($department)]);
    }

    public function destroy(Department $department): JsonResponse
    {
        try {
            $this->departments->delete($department);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(null, 204);
    }
}
