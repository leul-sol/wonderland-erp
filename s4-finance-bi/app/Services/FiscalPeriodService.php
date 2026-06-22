<?php

namespace App\Services;

use App\Models\FiscalPeriod;
use Carbon\Carbon;

class FiscalPeriodService
{
    public function forDate(Carbon|string $date): FiscalPeriod
    {
        $date = Carbon::parse($date)->startOfDay();

        $period = FiscalPeriod::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();

        if ($period === null) {
            throw new \RuntimeException('No fiscal period exists for entry date '.$date->toDateString());
        }

        return $period;
    }

    public function assertAllowsPosting(FiscalPeriod $period): void
    {
        if (! $period->allowsPosting()) {
            throw new \App\Exceptions\ClosedPeriodException(
                'Fiscal period '.$period->year.'-P'.$period->period_number.' does not accept new entries.'
            );
        }
    }
}
