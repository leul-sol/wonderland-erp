<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Services\BiReportService;
use App\Services\ReportCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiReportController extends Controller
{
    use RespondsWithApiErrors;

    public function __construct(
        private readonly BiReportService $biReports,
        private readonly ReportCatalogService $catalog,
    ) {
    }

    public function catalog(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->catalog->catalog($request->input('category'))]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        try {
            $data = $this->catalog->run(
                $slug,
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

    public function revenueBySource(Request $request): JsonResponse
    {
        try {
            $data = $this->biReports->revenueBySource(
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

    public function payrollSnapshot(): JsonResponse
    {
        try {
            $data = $this->biReports->payrollSnapshot();
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }

    public function hospitalitySnapshot(): JsonResponse
    {
        try {
            $data = $this->biReports->hospitalitySnapshot();
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }
}
