<?php

namespace App\Services;

use App\Models\FiscalPeriod;
use Carbon\Carbon;

class FiscalPeriodService
{
    public function __construct(
        private readonly AccountPeriodBalanceService $periodBalances,
        private readonly OutboxService $outbox,
    ) {
    }

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

    public function close(FiscalPeriod $period, int $closedBy): FiscalPeriod
    {
        if ($period->status === 'open') {
            $period->update(['status' => 'closing']);

            return $period->fresh();
        }

        if ($period->status === 'closing') {
            $this->periodBalances->closePeriod($period);

            $period->update([
                'status' => 'closed',
                'closed_by' => $closedBy,
                'closed_at' => now(),
            ]);

            $period = $period->fresh();

            $this->outbox->enqueue(config('events.channels.period_closed'), [
                'fiscal_period_id' => $period->id,
                'year' => $period->year,
                'period_number' => $period->period_number,
                'closed_by' => $closedBy,
                'closed_at' => $period->closed_at?->toIso8601String(),
            ]);

            return $period;
        }

        throw new \InvalidArgumentException('Only open or closing fiscal periods can be closed.');
    }

    public function lock(FiscalPeriod $period): FiscalPeriod
    {
        if ($period->status !== 'closed') {
            throw new \InvalidArgumentException('Only closed fiscal periods can be locked.');
        }

        $period->update(['status' => 'locked']);

        return $period->fresh();
    }

    public function create(array $data): FiscalPeriod
    {
        $year = (int) $data['year'];
        $periodNumber = (int) $data['period_number'];
        $startDate = Carbon::parse($data['start_date'])->toDateString();
        $endDate = Carbon::parse($data['end_date'])->toDateString();

        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('start_date must be on or before end_date.');
        }

        if (FiscalPeriod::query()->where('year', $year)->where('period_number', $periodNumber)->exists()) {
            throw new \InvalidArgumentException('Fiscal period already exists for this year and period number.');
        }

        $overlap = FiscalPeriod::query()
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();

        if ($overlap) {
            throw new \InvalidArgumentException('Fiscal period dates overlap an existing period.');
        }

        return FiscalPeriod::query()->create([
            'year' => $year,
            'period_number' => $periodNumber,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
        ]);
    }
}
