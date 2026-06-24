<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountPeriodBalance;
use App\Models\FiscalPeriod;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class AccountPeriodBalanceService
{
    public function __construct(private readonly ReportService $reports)
    {
    }

    public function closePeriod(FiscalPeriod $period): void
    {
        $accounts = Account::query()->where('is_active', true)->orderBy('code')->get();
        $activity = $this->reports->aggregateActivity(
            $period->start_date->toDateString(),
            $period->end_date->toDateString()
        );
        $cumulative = $this->reports->aggregateActivity(null, $period->end_date->toDateString());

        DB::transaction(function () use ($accounts, $activity, $cumulative, $period) {
            foreach ($accounts as $account) {
                $totals = $activity->get($account->id, ['debit' => 0.0, 'credit' => 0.0]);
                $ending = $this->reports->signedBalance(
                    $account,
                    (float) ($cumulative->get($account->id)['debit'] ?? 0),
                    (float) ($cumulative->get($account->id)['credit'] ?? 0),
                );

                $previous = AccountPeriodBalance::query()
                    ->where('account_id', $account->id)
                    ->whereHas('fiscalPeriod', fn ($q) => $q->where('end_date', '<', $period->start_date))
                    ->orderByDesc('fiscal_period_id')
                    ->first();

                AccountPeriodBalance::query()->updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'fiscal_period_id' => $period->id,
                    ],
                    [
                        'opening_balance' => $previous?->ending_balance ?? 0,
                        'ending_balance' => $ending,
                        'total_debit' => $totals['debit'],
                        'total_credit' => $totals['credit'],
                    ]
                );
            }
        });
    }
}
