<?php

namespace App\Http\Controllers;

use App\Models\FiscalPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiscalPeriodController extends Controller
{
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
            'data' => $query->get()->map(fn (FiscalPeriod $period) => [
                'id' => $period->id,
                'year' => $period->year,
                'period_number' => $period->period_number,
                'start_date' => $period->start_date?->toDateString(),
                'end_date' => $period->end_date?->toDateString(),
                'status' => $period->status,
            ])->values(),
        ]);
    }
}
