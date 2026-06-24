<?php

namespace App\Services\Api;

use Illuminate\Http\Client\Response;

class S4FinanceClient extends GatewayClient
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function payables(?string $status = 'open', array $query = []): array
    {
        $params = array_merge(['per_page' => 50], $query);
        if ($status) {
            $params['status'] = $status;
        }

        return $this->json('GET', '/s4/api/v1/payables', $params);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function settlePayable(int $payableId, array $payload): array
    {
        return $this->json('POST', "/s4/api/v1/payables/{$payableId}/settle", $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function receivables(?string $status = 'open', array $query = []): array
    {
        $params = array_merge(['per_page' => 50], $query);
        if ($status) {
            $params['status'] = $status;
        }

        return $this->json('GET', '/s4/api/v1/receivables', $params);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function settleReceivable(int $receivableId, array $payload): array
    {
        return $this->json('POST', "/s4/api/v1/receivables/{$receivableId}/settle", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function writeOffReceivable(int $receivableId, array $payload): array
    {
        return $this->json('POST', "/s4/api/v1/receivables/{$receivableId}/write-off", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function fiscalPeriods(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/fiscal-periods', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function closeFiscalPeriod(int $fiscalPeriodId): array
    {
        return $this->json('POST', "/s4/api/v1/fiscal-periods/{$fiscalPeriodId}/close");
    }

    /**
     * @return array<string, mixed>
     */
    public function lockFiscalPeriod(int $fiscalPeriodId): array
    {
        return $this->json('POST', "/s4/api/v1/fiscal-periods/{$fiscalPeriodId}/lock");
    }

    /**
     * @return array<string, mixed>
     */
    public function accounts(): array
    {
        return $this->json('GET', '/s4/api/v1/accounts');
    }

    /**
     * @return array<string, mixed>
     */
    public function journalEntries(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/journal-entries', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function journalEntry(int $journalEntryId): array
    {
        return $this->json('GET', "/s4/api/v1/journal-entries/{$journalEntryId}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createJournalEntry(array $payload, string $idempotencyKey): array
    {
        return $this->json('POST', '/s4/api/v1/journal-entries', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function approveJournalEntry(int $journalEntryId): array
    {
        return $this->json('POST', "/s4/api/v1/journal-entries/{$journalEntryId}/approve");
    }

    /**
     * @return array<string, mixed>
     */
    public function postJournalEntry(int $journalEntryId): array
    {
        return $this->json('POST', "/s4/api/v1/journal-entries/{$journalEntryId}/post");
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteJournalEntry(int $journalEntryId): array
    {
        return $this->json('DELETE', "/s4/api/v1/journal-entries/{$journalEntryId}");
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function trialBalance(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/reports/trial-balance', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function incomeStatement(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/reports/income-statement', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function balanceSheet(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/reports/balance-sheet', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function budgetVariance(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/bi/reports/budget_variance', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function executiveDashboard(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/dashboards/executive', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function operationsDashboard(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/dashboards/operations', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return Response
     */
    public function downloadFinancialReport(string $report, string $format, array $query = []): Response
    {
        $query['export'] = $format;

        $path = match ($report) {
            'income_statement' => '/s4/api/v1/reports/income-statement',
            'balance_sheet' => '/s4/api/v1/reports/balance-sheet',
            default => '/s4/api/v1/reports/trial-balance',
        };

        return $this->send('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Response
     */
    public function exportReport(array $payload): Response
    {
        return $this->send('POST', '/s4/api/v1/bi/exports', $payload);
    }
}
