<?php

namespace App\Http\Controllers\Finance;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\DefersGatewayPageData;
use App\Http\Controllers\Concerns\HandlesPortalApiErrors;
use App\Http\Controllers\Controller;
use App\Services\Api\S4FinanceClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use DefersGatewayPageData;
    use HandlesPortalApiErrors;

    public function __construct(
        private readonly S4FinanceClient $s4,
    ) {
    }

    public function index(Request $request): Response
    {
        $type = (string) $request->input('type', 'trial_balance');
        $query = array_filter([
            'fiscal_period_id' => $request->input('fiscal_period_id'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ], fn ($value) => $value !== null && $value !== '');

        return Inertia::render('Finance/Reports/Index', [
            'reportType' => in_array($type, ['trial_balance', 'income_statement', 'balance_sheet', 'cash_flow', 'departmental'], true)
                ? $type
                : 'trial_balance',
            'filters' => [
                'fiscal_period_id' => $request->input('fiscal_period_id'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
            'report' => $this->deferApi(function () use ($type, $query) {
                $response = match ($type) {
                    'income_statement' => $this->s4->incomeStatement($query),
                    'balance_sheet' => $this->s4->balanceSheet($query),
                    'cash_flow' => $this->s4->cashFlow($query),
                    'departmental' => $this->s4->departmental($query),
                    default => $this->s4->trialBalance($query),
                };

                return $response['data'] ?? [];
            }),
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $data = $request->validate([
            'report' => ['required', 'in:trial_balance,income_statement,balance_sheet,cash_flow,departmental,budget_variance'],
            'format' => ['required', 'in:csv,pdf,excel'],
            'fiscal_period_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = array_filter([
            'fiscal_period_id' => $data['fiscal_period_id'] ?? null,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        try {
            if (in_array($data['report'], ['trial_balance', 'income_statement', 'balance_sheet', 'cash_flow', 'departmental'], true)) {
                $response = $this->s4->downloadFinancialReport($data['report'], $data['format'], $query);
            } else {
                $response = $this->s4->exportReport([
                    'report' => $data['report'],
                    'format' => $data['format'],
                    ...$query,
                ]);
            }
        } catch (ApiException $e) {
            return $this->redirectApiError($e, 'finance.reports.index');
        }

        $filename = $data['report'].'.'.$data['format'];
        $disposition = $response->header('Content-Disposition') ?: 'attachment; filename="'.$filename.'"';

        return response()->streamDownload(function () use ($response): void {
            echo $response->body();
        }, $filename, [
            'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'Content-Disposition' => $disposition,
        ]);
    }
}
