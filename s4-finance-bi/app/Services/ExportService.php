<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly BiReportService $biReports,
    ) {
    }

    public function export(string $report, string $format, ?int $fiscalPeriodId, ?string $from, ?string $to): StreamedResponse
    {
        if ($format !== 'csv') {
            throw new \InvalidArgumentException('Only csv export is supported.');
        }

        $data = $this->resolveReport($report, $fiscalPeriodId, $from, $to);
        $filename = $report.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data, $report) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            foreach ($this->csvRows($report, $data) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveReport(string $report, ?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        return match ($report) {
            'trial_balance' => $this->reports->trialBalance($fiscalPeriodId, $from, $to),
            'income_statement' => $this->reports->incomeStatement($fiscalPeriodId, $from, $to),
            'balance_sheet' => $this->reports->balanceSheet($fiscalPeriodId, $from, $to),
            'cash_flow' => $this->reports->cashFlow($fiscalPeriodId, $from, $to),
            'revenue_by_source' => $this->biReports->revenueBySource($fiscalPeriodId, $from, $to),
            default => throw new \InvalidArgumentException('Unknown report type: '.$report),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function csvRows(string $report, array $data): array
    {
        return match ($report) {
            'trial_balance' => $this->trialBalanceRows($data),
            'income_statement' => $this->incomeStatementRows($data),
            'revenue_by_source' => $this->revenueBySourceRows($data),
            default => [['report', (string) ($data['report'] ?? $report)], ['generated', now()->toIso8601String()]],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function trialBalanceRows(array $data): array
    {
        $rows = [['account_code', 'account_name', 'period_debit', 'period_credit', 'ending_balance']];
        foreach ($data['lines'] as $line) {
            $rows[] = [
                (string) $line['account_code'],
                (string) $line['account_name'],
                (string) $line['period_debit'],
                (string) $line['period_credit'],
                (string) $line['ending_balance'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function incomeStatementRows(array $data): array
    {
        $rows = [['section', 'account_code', 'account_name', 'amount']];
        foreach ($data['revenue']['lines'] as $line) {
            $rows[] = ['revenue', (string) $line['account_code'], (string) $line['account_name'], (string) $line['amount']];
        }
        foreach ($data['expenses']['lines'] as $line) {
            $rows[] = ['expense', (string) $line['account_code'], (string) $line['account_name'], (string) $line['amount']];
        }
        $rows[] = ['summary', '', 'net_income', (string) $data['net_income']];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function revenueBySourceRows(array $data): array
    {
        $rows = [['source_module', 'entry_count', 'volume']];
        foreach ($data['lines'] as $line) {
            $rows[] = [
                (string) $line['source_module'],
                (string) $line['entry_count'],
                (string) $line['volume'],
            ];
        }

        return $rows;
    }
}
