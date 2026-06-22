<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Requests\StoreEmployeeConsumptionPeriodRequest;
use App\Models\EmployeeConsumptionPeriod;
use App\Services\EmployeeConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class EmployeeConsumptionController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly EmployeeConsumptionService $consumption)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = EmployeeConsumptionPeriod::query()->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($period) => $this->payload($period))->values(),
        ]);
    }

    public function store(StoreEmployeeConsumptionPeriodRequest $request): JsonResponse
    {
        try {
            $period = $this->consumption->open($request->validated());
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->payload($period)], 201);
    }

    public function close(EmployeeConsumptionPeriod $employeeConsumptionPeriod): JsonResponse
    {
        try {
            $period = $this->consumption->close($employeeConsumptionPeriod);
        } catch (InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $this->payload($period)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(EmployeeConsumptionPeriod $period): array
    {
        return [
            'id' => $period->id,
            'employee_id' => $period->employee_id,
            'period_start' => $period->period_start?->toDateString(),
            'period_end' => $period->period_end?->toDateString(),
            'total_amount' => (string) $period->total_amount,
            'status' => $period->status,
            'closed_at' => $period->closed_at?->toIso8601String(),
        ];
    }
}
