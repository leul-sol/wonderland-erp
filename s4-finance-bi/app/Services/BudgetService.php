<?php

namespace App\Services;

use App\Models\BudgetLine;
use App\Models\FiscalPeriod;
use InvalidArgumentException;

class BudgetService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertLine(array $data): BudgetLine
    {
        $period = FiscalPeriod::query()->findOrFail($data['fiscal_period_id']);

        return BudgetLine::query()->updateOrCreate(
            [
                'fiscal_period_id' => $period->id,
                'account_code' => $data['account_code'],
            ],
            ['budget_amount' => $data['budget_amount']]
        );
    }

    public function budgetNetIncome(int $fiscalPeriodId): float
    {
        $lines = BudgetLine::query()->where('fiscal_period_id', $fiscalPeriodId)->get();

        if ($lines->isEmpty()) {
            return 0.0;
        }

        $revenue = 0.0;
        $expense = 0.0;

        foreach ($lines as $line) {
            $code = (string) $line->account_code;
            $amount = (float) $line->budget_amount;

            if (str_starts_with($code, '4')) {
                $revenue += $amount;
            } elseif (str_starts_with($code, '5')) {
                $expense += $amount;
            }
        }

        return round($revenue - $expense, 2);
    }

    public function assertValidAccountCode(string $code): void
    {
        if (! preg_match('/^\d{4}$/', $code)) {
            throw new InvalidArgumentException('account_code must be a four-digit code.');
        }
    }
}
