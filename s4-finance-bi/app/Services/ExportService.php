<?php

namespace App\Services;

use Illuminate\Http\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function __construct(
        private readonly ReportCatalogService $catalog,
        private readonly PdfExportService $pdf,
        private readonly ExcelExportService $excel,
        private readonly S2WorkforceClient $s2,
    ) {
    }

    public function export(
        string $report,
        string $format,
        ?int $fiscalPeriodId,
        ?string $from,
        ?string $to,
        ?int $employeeId = null,
        ?int $payrollRunId = null,
        ?int $guarantorId = null,
        ?string $generatedBy = null,
    ): StreamedResponse|Response|\Illuminate\Http\JsonResponse {
        if ($report === 'payroll_payslip' && $format === 'pdf') {
            return $this->exportPayslipPdf($employeeId, $payrollRunId);
        }

        if ($report === 'hr_guarantor_letter' && $format === 'pdf') {
            return $this->exportGuarantorLetterPdf($employeeId, $guarantorId);
        }

        $data = $this->catalog->run($report, $fiscalPeriodId, $from, $to);
        $rows = $this->tabularRows($report, $data);
        $filename = $report.'-'.now()->format('Ymd-His');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, $filename.'.csv', ['Content-Type' => 'text/csv']);
        }

        if ($format === 'pdf') {
            return $this->pdf->download((string) ($data['name'] ?? $report), $rows, $filename.'.pdf', [
                'report_slug' => $report,
                'from' => $from,
                'to' => $to,
                'fiscal_period_id' => $fiscalPeriodId,
                'generated_by' => $generatedBy,
            ]);
        }

        if ($format === 'excel') {
            return $this->excel->download((string) ($data['name'] ?? $report), $rows, $filename.'.xls');
        }

        throw new InvalidArgumentException('Unsupported export format: '.$format);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function tabularRows(string $report, array $data): array
    {
        return match ($report) {
            'trial_balance' => $this->trialBalanceRows($data),
            'income_statement' => $this->incomeStatementRows($data),
            'ar_aging', 'ap_aging' => $this->agingRows($data),
            'gl_detail' => $this->glDetailRows($data),
            'revenue_by_source' => $this->revenueBySourceRows($data),
            default => $this->genericRows($data),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function trialBalanceRows(array $data): array
    {
        $rows = [['account_code', 'account_name', 'period_debit', 'period_credit', 'ending_balance']];
        foreach ($data['lines'] ?? [] as $line) {
            $rows[] = [(string) $line['account_code'], (string) $line['account_name'], (string) $line['period_debit'], (string) $line['period_credit'], (string) $line['ending_balance']];
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
        foreach ($data['revenue']['lines'] ?? [] as $line) {
            $rows[] = ['revenue', (string) $line['account_code'], (string) $line['account_name'], (string) $line['amount']];
        }
        foreach ($data['cogs']['lines'] ?? [] as $line) {
            $rows[] = ['cogs', (string) $line['account_code'], (string) $line['account_name'], (string) $line['amount']];
        }
        $rows[] = ['summary', '', 'gross_profit', (string) ($data['gross_profit'] ?? '')];
        foreach ($data['operating_expenses']['lines'] ?? [] as $line) {
            $rows[] = ['operating_expense', (string) $line['account_code'], (string) $line['account_name'], (string) $line['amount']];
        }
        $rows[] = ['summary', '', 'net_income', (string) ($data['net_income'] ?? '')];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function agingRows(array $data): array
    {
        $rows = [['reference', 'party', 'account_code', 'balance']];
        foreach ($data['lines'] ?? [] as $line) {
            $rows[] = [
                (string) ($line['source_reference'] ?? ''),
                (string) ($line['party_name'] ?? $line['vendor_name'] ?? ''),
                (string) ($line['account_code'] ?? ''),
                (string) ($line['balance'] ?? ''),
            ];
        }
        $rows[] = ['total', '', '', (string) ($data['total_outstanding'] ?? '')];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function glDetailRows(array $data): array
    {
        $rows = [['entry_number', 'entry_date', 'account_code', 'debit', 'credit', 'description']];
        foreach ($data['lines'] ?? [] as $line) {
            $rows[] = [
                (string) $line['entry_number'],
                (string) $line['entry_date'],
                (string) $line['account_code'],
                (string) $line['debit'],
                (string) $line['credit'],
                (string) $line['description'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function revenueBySourceRows(array $data): array
    {
        $rows = [['source_module', 'entry_count', 'volume']];
        foreach ($data['lines'] ?? [] as $line) {
            $rows[] = [(string) $line['source_module'], (string) $line['entry_count'], (string) $line['volume']];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<int, string>>
     */
    private function genericRows(array $data): array
    {
        if (isset($data['lines']) && is_array($data['lines']) && $data['lines'] !== []) {
            $first = $data['lines'][0] ?? null;
            if (is_array($first)) {
                $headers = array_keys($first);
                $rows = [$headers];
                foreach ($data['lines'] as $line) {
                    if (! is_array($line)) {
                        continue;
                    }
                    $rows[] = array_map(fn (string $header) => (string) ($line[$header] ?? ''), $headers);
                }

                return $rows;
            }
        }

        $rows = [['key', 'value']];
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $rows[] = [(string) $key, (string) $value];
            }
        }

        return $rows;
    }

    private function exportPayslipPdf(?int $employeeId, ?int $payrollRunId): Response
    {
        if ($employeeId === null || $payrollRunId === null) {
            throw new InvalidArgumentException('employee_id and payroll_run_id are required for payslip PDF export.');
        }

        $binary = $this->s2->payslipPdf($employeeId, $payrollRunId);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="payslip-'.$employeeId.'-'.$payrollRunId.'.pdf"',
        ]);
    }

    private function exportGuarantorLetterPdf(?int $employeeId, ?int $guarantorId): Response
    {
        if ($employeeId === null || $guarantorId === null) {
            throw new InvalidArgumentException('employee_id and guarantor_id are required for guarantor letter PDF export.');
        }

        $binary = $this->s2->guarantorLetterPdf($employeeId, $guarantorId);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="guarantor-letter-'.$employeeId.'-'.$guarantorId.'.pdf"',
        ]);
    }
}
