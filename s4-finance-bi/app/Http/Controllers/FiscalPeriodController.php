<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\SerializesAccounts;
use App\Http\Requests\StoreFiscalPeriodRequest;
use App\Models\FiscalPeriod;
use App\Services\FiscalPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
    use RespondsWithApiErrors;
    use SerializesAccounts;

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
            'data' => $query->get()->map(fn (FiscalPeriod $period) => $this->fiscalPeriodPayload($period))->values(),
        ]);
    }

    public function store(StoreFiscalPeriodRequest $request): JsonResponse
    {
        try {
            $period = $this->fiscalPeriods->create($request->validated());
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->fiscalPeriodPayload($period)], 201);
    }

    public function close(Request $request, FiscalPeriod $fiscalPeriod): JsonResponse
    {
        $userId = (int) $request->attributes->get('auth_user_id', 0);

        try {
            $period = $this->fiscalPeriods->close($fiscalPeriod, $userId);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->fiscalPeriodPayload($period)]);
    }

    public function lock(FiscalPeriod $fiscalPeriod): JsonResponse
    {
        try {
            $period = $this->fiscalPeriods->lock($fiscalPeriod);
        } catch (\InvalidArgumentException $e) {
            return $this->error('INVALID_STATE', $e->getMessage(), 422);
        }

        return response()->json(['data' => $this->fiscalPeriodPayload($period)]);
    }
}
