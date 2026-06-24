<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\RespondsWithReportExport;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use RespondsWithApiErrors;
    use RespondsWithReportExport;

    public function __construct(
        private readonly ReportService $reports,
        private readonly ExportService $exports,
    ) {
    }

    public function trialBalance(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $periodId = $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null;
            $from = $request->input('from');
            $to = $request->input('to');
            $data = $this->reports->trialBalance($periodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return $this->respondWithReport($request, $this->exports, 'trial_balance', $data, $periodId, $from, $to);
    }

    public function incomeStatement(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $periodId = $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null;
            $from = $request->input('from');
            $to = $request->input('to');
            $data = $this->reports->incomeStatement($periodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return $this->respondWithReport($request, $this->exports, 'income_statement', $data, $periodId, $from, $to);
    }

    public function profitLoss(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        return $this->incomeStatement($request);
    }

    public function balanceSheet(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $periodId = $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null;
            $from = $request->input('from');
            $to = $request->input('to');
            $data = $this->reports->balanceSheet($periodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return $this->respondWithReport($request, $this->exports, 'balance_sheet', $data, $periodId, $from, $to);
    }

    public function cashFlow(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $periodId = $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null;
            $from = $request->input('from');
            $to = $request->input('to');
            $data = $this->reports->cashFlow($periodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return $this->respondWithReport($request, $this->exports, 'cash_flow', $data, $periodId, $from, $to);
    }

    public function departmental(Request $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        try {
            $periodId = $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null;
            $from = $request->input('from');
            $to = $request->input('to');
            $data = $this->reports->departmental($periodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        }

        return $this->respondWithReport($request, $this->exports, 'departmental', $data, $periodId, $from, $to);
    }
}
