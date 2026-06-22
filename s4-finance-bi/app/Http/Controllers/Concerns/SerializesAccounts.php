<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Account;
use App\Models\FiscalPeriod;

trait SerializesAccounts
{
    protected function accountPayload(Account $account): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'sub_type' => $account->sub_type,
            'normal_balance' => $account->normal_balance,
            'is_active' => $account->is_active,
            'parent_id' => $account->parent_id,
        ];
    }

    protected function fiscalPeriodPayload(FiscalPeriod $period): array
    {
        return [
            'id' => $period->id,
            'year' => $period->year,
            'period_number' => $period->period_number,
            'start_date' => $period->start_date?->toDateString(),
            'end_date' => $period->end_date?->toDateString(),
            'status' => $period->status,
            'closed_by' => $period->closed_by,
            'closed_at' => $period->closed_at?->toIso8601String(),
        ];
    }
}
