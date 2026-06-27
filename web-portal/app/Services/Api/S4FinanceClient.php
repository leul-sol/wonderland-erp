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
    public function accounts(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/accounts', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function createFiscalPeriod(array $payload): array
    {
        return $this->json('POST', '/s4/api/v1/fiscal-periods', $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function budgetLines(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/finance/budgets', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createBudgetLine(array $payload): array
    {
        return $this->json('POST', '/s4/api/v1/finance/budgets', $payload);
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

    public function hotelDashboard(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/dashboard/hotel', $query);
    }

    public function restaurantDashboard(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/dashboard/restaurant', $query);
    }

    public function financeDashboard(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/dashboard/finance', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function biReportCatalog(?string $category = null): array
    {
        $query = $category ? ['category' => $category] : [];

        return $this->json('GET', '/s4/api/v1/bi/reports', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function biReport(string $slug, array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/bi/reports/'.$slug, $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function rtmEntries(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/rtm', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function rtmEntry(int $rtmEntryId): array
    {
        return $this->json('GET', "/s4/api/v1/bi/rtm/{$rtmEntryId}");
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateRtmEntry(int $rtmEntryId, array $payload): array
    {
        return $this->json('PUT', "/s4/api/v1/rtm/{$rtmEntryId}", $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function uatScenarios(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/bi/uat', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function recordUatResult(int $uatScenarioId, array $payload): array
    {
        return $this->json('POST', "/s4/api/v1/bi/uat/{$uatScenarioId}/results", $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function reverseJournalEntry(int $journalEntryId, array $payload = []): array
    {
        return $this->json('POST', "/s4/api/v1/journal-entries/{$journalEntryId}/reverse", $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function cashFlow(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/reports/cash-flow', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function departmental(array $query = []): array
    {
        return $this->json('GET', '/s4/api/v1/reports/departmental', $query);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function createAccount(array $payload): array
    {
        return $this->json('POST', '/s4/api/v1/accounts', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function updateAccount(int $accountId, array $payload): array
    {
        return $this->json('PUT', "/s4/api/v1/accounts/{$accountId}", $payload);
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
            'cash_flow' => '/s4/api/v1/reports/cash-flow',
            'departmental' => '/s4/api/v1/reports/departmental',
            default => '/s4/api/v1/reports/trial-balance',
        };

        return $this->send('GET', $path, $query);
    }

    public function downloadBiReport(string $slug, string $format, array $query = []): Response
    {
        $query['export'] = $format;

        return $this->send('GET', '/s4/api/v1/bi/reports/'.$slug, $query);
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
