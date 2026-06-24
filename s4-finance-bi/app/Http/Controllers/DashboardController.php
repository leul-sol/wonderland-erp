<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\BiReportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(
        private readonly ReportService $reports,
        private readonly BiReportService $biReports,
    ) {
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

    public function operations(Request $request): JsonResponse
    {
        try {
            $data = $this->biReports->operationsDashboard(
                $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
                $request->input('from'),
                $request->input('to'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }

    public function hotel(Request $request): JsonResponse
    {
        try {
            $data = $this->biReports->hotelDashboard();
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }

    public function restaurant(Request $request): JsonResponse
    {
        try {
            $data = $this->biReports->restaurantDashboard();
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }

    public function finance(Request $request): JsonResponse
    {
        try {
            $data = $this->reports->financeDashboard(
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
