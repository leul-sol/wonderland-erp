<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Models\FiscalPeriod;
use App\Services\FiscalPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly FiscalPeriodService $fiscalPeriods)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = FiscalPeriod::query()->orderBy('year')->orderBy('period_number');

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (FiscalPeriod $period) => $this->periodPayload($period))->values(),
        ]);
    }

    public function close(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $period = $this->fiscalPeriods->close($fiscalPeriod, $userId);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->periodPayload($period)]);
    }

    public function lock(FiscalPeriod $fiscalPeriod): JsonResponse
    {
        try {
            $period = $this->fiscalPeriods->lock($fiscalPeriod);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->periodPayload($period)]);
    }

    private function periodPayload(FiscalPeriod $period): array
    {
        return [
            'id' => $period->id,
            'year' => $period->year,
            'period_number' => $period->period_number,
            'start_date' => $period->start_date?->toDateString(),
            'end_date' => $period->end_date?->toDateString(),
            'status' => $period->status,
            'closed_by' => $period->closed_by,
            'closed_at' => $period->closed_at?->toIso8601String(),
        ];
    }
}
