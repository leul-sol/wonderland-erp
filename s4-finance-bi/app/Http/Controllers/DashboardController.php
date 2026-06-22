<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(private readonly ReportService $reports)
    {
    }

    public function executive(Request $request): JsonResponse
    {
        try {
            $data = $this->reports->executiveDashboard(
                $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
                $request->input('from'),
                $request->input('to'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return response()->json(['data' => $data]);
    }
}
