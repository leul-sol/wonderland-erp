<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesWorkforceResources;
use App\Http\Requests\StoreEmployeeDeductionRequest;
use App\Models\Employee;
use App\Models\SeveranceCalculation;
use App\Services\DeductionService;
use App\Services\SeveranceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EmployeeDeductionController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesWorkforceResources;

    public function __construct(
        private readonly DeductionService $deductions,
        private readonly SeveranceService $severance,
    ) {
    }

    public function store(StoreEmployeeDeductionRequest $request, Employee $employee): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey === null || $idempotencyKey === '') {
            return $this->error('VALIDATION_ERROR', 'Idempotency-Key header is required.', 422);
        }

        try {
            $deduction = $this->deductions->apply($employee, $request->validated(), $idempotencyKey);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->deductionPayload($deduction)], 201);
    }

    public function calculateSeverance(Employee $employee): JsonResponse
    {
        try {
            $calculation = $this->severance->calculate($employee);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->severancePayload($calculation)], 201);
    }

    public function paySeverance(SeveranceCalculation $severanceCalculation): JsonResponse
    {
        try {
            $calculation = $this->severance->pay($severanceCalculation);
        } catch (InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->severancePayload($calculation)]);
    }

    public function severanceIndex(Request $request): JsonResponse
    {
        $query = SeveranceCalculation::query()->with('employee')->orderByDesc('id');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($row) => $this->severancePayload($row))->values(),
        ]);
    }
}
