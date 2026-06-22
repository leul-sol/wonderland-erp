<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalLine;
use App\Models\Payable;
use App\Models\Receivable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array{fiscal_period: ?FiscalPeriod, from: string, to: string}
     */
    public function resolveDateRange(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        if ($fiscalPeriodId !== null) {
            $period = FiscalPeriod::query()->findOrFail($fiscalPeriodId);

            return [
                'fiscal_period' => $period,
                'from' => $period->start_date->toDateString(),
                'to' => $period->end_date->toDateString(),
            ];
        }

        $fromDate = $from !== null ? Carbon::parse($from)->toDateString() : now()->startOfMonth()->toDateString();
        $toDate = $to !== null ? Carbon::parse($to)->toDateString() : now()->toDateString();

        if ($fromDate > $toDate) {
            throw new \InvalidArgumentException('from date must be on or before to date.');
        }

        $period = FiscalPeriod::query()
            ->whereDate('start_date', '<=', $toDate)
            ->whereDate('end_date', '>=', $fromDate)
            ->orderBy('start_date')
            ->first();

        return [
            'fiscal_period' => $period,
            'from' => $fromDate,
            'to' => $toDate,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trialBalance(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange($fiscalPeriodId, $from, $to);
        $periodActivity = $this->aggregateActivity($range['from'], $range['to']);
        $cumulative = $this->aggregateActivity(null, $range['to']);

        $accounts = Account::query()->where('is_active', true)->orderBy('code')->get();
        $lines = [];
        $totalDebits = 0.0;
        $totalCredits = 0.0;

        foreach ($accounts as $account) {
            $activity = $periodActivity->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $ending = $this->signedBalance(
                $account,
                (float) ($cumulative->get($account->id)['debit'] ?? 0),
                (float) ($cumulative->get($account->id)['credit'] ?? 0),
            );

            if (
                round((float) $activity['debit'], 2) === 0.0
                && round((float) $activity['credit'], 2) === 0.0
                && round($ending, 2) === 0.0
            ) {
                continue;
            }

            $debitBalance = $ending > 0 && $account->normal_balance === 'debit' ? $ending : 0.0;
            $creditBalance = $ending > 0 && $account->normal_balance === 'credit' ? $ending : 0.0;

            if ($account->normal_balance === 'debit' && $ending < 0) {
                $creditBalance = abs($ending);
            } elseif ($account->normal_balance === 'credit' && $ending < 0) {
                $debitBalance = abs($ending);
            }

            $totalDebits += $debitBalance;
            $totalCredits += $creditBalance;

            $lines[] = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'period_debit' => $this->formatMoney((float) $activity['debit']),
                'period_credit' => $this->formatMoney((float) $activity['credit']),
                'ending_balance' => $this->formatMoney($ending),
                'debit_balance' => $this->formatMoney($debitBalance),
                'credit_balance' => $this->formatMoney($creditBalance),
            ];
        }

        return [
            'report' => 'trial_balance',
            'from' => $range['from'],
            'to' => $range['to'],
            'fiscal_period' => $this->periodMeta($range['fiscal_period']),
            'lines' => $lines,
            'totals' => [
                'debit' => $this->formatMoney($totalDebits),
                'credit' => $this->formatMoney($totalCredits),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function incomeStatement(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange($fiscalPeriodId, $from, $to);
        $activity = $this->aggregateActivity($range['from'], $range['to']);
        $accounts = Account::query()->whereIn('type', ['income', 'expense'])->orderBy('code')->get();

        $revenueLines = [];
        $expenseLines = [];
        $totalRevenue = 0.0;
        $totalExpenses = 0.0;

        foreach ($accounts as $account) {
            $totals = $activity->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $amount = $this->signedBalance($account, (float) $totals['debit'], (float) $totals['credit']);

            if (round($amount, 2) === 0.0) {
                continue;
            }

            $line = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'amount' => $this->formatMoney(abs($amount)),
            ];

            if ($account->type === 'income') {
                $revenueLines[] = $line;
                $totalRevenue += abs($amount);
            } else {
                $expenseLines[] = $line;
                $totalExpenses += abs($amount);
            }
        }

        $netIncome = round($totalRevenue - $totalExpenses, 2);

        return [
            'report' => 'income_statement',
            'from' => $range['from'],
            'to' => $range['to'],
            'fiscal_period' => $this->periodMeta($range['fiscal_period']),
            'revenue' => [
                'lines' => $revenueLines,
                'total' => $this->formatMoney($totalRevenue),
            ],
            'expenses' => [
                'lines' => $expenseLines,
                'total' => $this->formatMoney($totalExpenses),
            ],
            'net_income' => $this->formatMoney($netIncome),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceSheet(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange($fiscalPeriodId, $from, $to);
        $cumulative = $this->aggregateActivity(null, $range['to']);
        $accounts = Account::query()
            ->whereIn('type', ['asset', 'liability'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $assets = [];
        $liabilities = [];
        $totalAssets = 0.0;
        $totalLiabilities = 0.0;

        foreach ($accounts as $account) {
            $totals = $cumulative->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $balance = $this->signedBalance($account, (float) $totals['debit'], (float) $totals['credit']);

            if (round($balance, 2) === 0.0) {
                continue;
            }

            $line = [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'balance' => $this->formatMoney($balance),
            ];

            if ($account->type === 'asset') {
                $assets[] = $line;
                $totalAssets += $balance;
            } else {
                $liabilities[] = $line;
                $totalLiabilities += $balance;
            }
        }

        $incomeStatement = $this->incomeStatement($fiscalPeriodId, $range['from'], $range['to']);
        $netIncome = (float) $incomeStatement['net_income'];
        $equity = round($totalAssets - $totalLiabilities, 2);

        return [
            'report' => 'balance_sheet',
            'as_of' => $range['to'],
            'fiscal_period' => $this->periodMeta($range['fiscal_period']),
            'assets' => [
                'lines' => $assets,
                'total' => $this->formatMoney($totalAssets),
            ],
            'liabilities' => [
                'lines' => $liabilities,
                'total' => $this->formatMoney($totalLiabilities),
            ],
            'equity' => [
                'current_period_net_income' => $this->formatMoney($netIncome),
                'total' => $this->formatMoney($equity),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cashFlow(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange($fiscalPeriodId, $from, $to);
        $income = $this->incomeStatement($fiscalPeriodId, $range['from'], $range['to']);
        $netIncome = (float) $income['net_income'];

        $cashCodes = ['1001', '1002', '1003', '1004', '1005'];
        $periodActivity = $this->aggregateActivity($range['from'], $range['to']);
        $openingCumulative = $this->aggregateActivity(null, Carbon::parse($range['from'])->subDay()->toDateString());
        $closingCumulative = $this->aggregateActivity(null, $range['to']);

        $periodCashChange = 0.0;
        $openingCash = 0.0;
        $closingCash = 0.0;

        $cashAccounts = Account::query()->whereIn('code', $cashCodes)->get();
        foreach ($cashAccounts as $account) {
            $periodTotals = $periodActivity->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $periodCashChange += $this->signedBalance($account, (float) $periodTotals['debit'], (float) $periodTotals['credit']);

            $openingTotals = $openingCumulative->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $openingCash += $this->signedBalance($account, (float) $openingTotals['debit'], (float) $openingTotals['credit']);

            $closingTotals = $closingCumulative->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $closingCash += $this->signedBalance($account, (float) $closingTotals['debit'], (float) $closingTotals['credit']);
        }

        return [
            'report' => 'cash_flow',
            'from' => $range['from'],
            'to' => $range['to'],
            'fiscal_period' => $this->periodMeta($range['fiscal_period']),
            'operating' => [
                'net_income' => $this->formatMoney($netIncome),
                'net_cash_from_operations' => $this->formatMoney($netIncome),
            ],
            'net_change_in_cash' => $this->formatMoney($periodCashChange),
            'opening_cash' => $this->formatMoney($openingCash),
            'closing_cash' => $this->formatMoney($closingCash),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function executiveDashboard(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange($fiscalPeriodId, $from, $to);
        $income = $this->incomeStatement($fiscalPeriodId, $range['from'], $range['to']);
        $cashCodes = ['1001', '1002', '1003', '1004', '1005'];
        $cumulative = $this->aggregateActivity(null, $range['to']);
        $cashPosition = 0.0;

        $cashAccounts = Account::query()->whereIn('code', $cashCodes)->get();
        foreach ($cashAccounts as $account) {
            $totals = $cumulative->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
            $cashPosition += $this->signedBalance($account, (float) $totals['debit'], (float) $totals['credit']);
        }

        $arOutstanding = (float) Receivable::query()->where('status', 'open')->sum('balance');
        $apOutstanding = (float) Payable::query()->where('status', 'open')->sum('balance');

        return [
            'dashboard' => 'executive',
            'from' => $range['from'],
            'to' => $range['to'],
            'fiscal_period' => $this->periodMeta($range['fiscal_period']),
            'kpis' => [
                'revenue' => $income['revenue']['total'],
                'expenses' => $income['expenses']['total'],
                'net_income' => $income['net_income'],
                'cash_position' => $this->formatMoney($cashPosition),
                'ar_outstanding' => $this->formatMoney($arOutstanding),
                'ap_outstanding' => $this->formatMoney($apOutstanding),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function glDetail(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->resolveDateRange($fiscalPeriodId, $from, $to);

        $lines = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '>=', $range['from'])
            ->whereDate('journal_entries.entry_date', '<=', $range['to'])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get([
                'journal_entries.entry_number',
                'journal_entries.entry_date',
                'journal_entries.description as entry_description',
                'journal_entries.source_module',
                'accounts.code as account_code',
                'accounts.name as account_name',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.description as line_description',
            ]);

        return [
            'report' => 'gl_detail',
            'from' => $range['from'],
            'to' => $range['to'],
            'lines' => $lines->map(fn ($line) => [
                'entry_number' => $line->entry_number,
                'entry_date' => $line->entry_date,
                'account_code' => $line->account_code,
                'account_name' => $line->account_name,
                'debit' => $this->formatMoney((float) $line->debit),
                'credit' => $this->formatMoney((float) $line->credit),
                'source_module' => $line->source_module,
                'description' => $line->line_description ?? $line->entry_description,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function arAging(): array
    {
        $receivables = Receivable::query()->with('account')->where('status', 'open')->orderByDesc('balance')->get();

        return [
            'report' => 'ar_aging',
            'as_of' => now()->toDateString(),
            'total_outstanding' => $this->formatMoney((float) $receivables->sum('balance')),
            'lines' => $receivables->map(fn ($r) => [
                'id' => $r->id,
                'party_name' => $r->party_name,
                'source_reference' => $r->source_reference,
                'account_code' => $r->account?->code,
                'balance' => $this->formatMoney((float) $r->balance),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function apAging(): array
    {
        $payables = Payable::query()->with('account')->where('status', 'open')->orderByDesc('balance')->get();

        return [
            'report' => 'ap_aging',
            'as_of' => now()->toDateString(),
            'total_outstanding' => $this->formatMoney((float) $payables->sum('balance')),
            'lines' => $payables->map(fn ($p) => [
                'id' => $p->id,
                'vendor_name' => $p->vendor_name,
                'source_reference' => $p->source_reference,
                'account_code' => $p->account?->code,
                'balance' => $this->formatMoney((float) $p->balance),
            ])->values()->all(),
        ];
    }

    /**
     * @return Collection<int, array{debit: float, credit: float}>
     */
    private function aggregateActivity(?string $from, string $to): Collection
    {
        $query = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->whereDate('journal_entries.entry_date', '<=', $to);

        if ($from !== null) {
            $query->whereDate('journal_entries.entry_date', '>=', $from);
        }

        return $query
            ->groupBy('journal_lines.account_id')
            ->select([
                'journal_lines.account_id',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit'),
            ])
            ->get()
            ->keyBy('account_id')
            ->map(fn ($row) => [
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
            ]);
    }

    private function signedBalance(Account $account, float $debit, float $credit): float
    {
        if ($account->normal_balance === 'credit') {
            return round($credit - $debit, 2);
        }

        return round($debit - $credit, 2);
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function periodMeta(?FiscalPeriod $period): ?array
    {
        if ($period === null) {
            return null;
        }

        return [
            'id' => $period->id,
            'year' => $period->year,
            'period_number' => $period->period_number,
            'status' => $period->status,
        ];
    }
}
