<?php

namespace App\Console\Commands;

use App\Services\FiscalPeriodService;
use App\Models\FiscalPeriod;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OpenNextFiscalPeriod extends Command
{
    protected $signature = 's4:fiscal-period:open';

    protected $description = 'Open the next fiscal period after the latest seeded period (Ethiopian FY month)';

    public function handle(FiscalPeriodService $fiscalPeriods): int
    {
        $latest = FiscalPeriod::query()->orderByDesc('end_date')->first();

        if ($latest === null) {
            $this->error('No fiscal periods exist. Run FiscalPeriodSeeder first.');

            return self::FAILURE;
        }

        $nextStart = Carbon::parse($latest->end_date)->addDay()->startOfDay();
        $exists = FiscalPeriod::query()
            ->whereDate('start_date', '<=', $nextStart)
            ->whereDate('end_date', '>=', $nextStart)
            ->exists();

        if ($exists) {
            $this->info('Fiscal period already covers '.$nextStart->toDateString().'.');

            return self::SUCCESS;
        }

        $periodNumber = (int) $latest->period_number + 1;
        $year = (int) $latest->year;

        if ($periodNumber > 12) {
            $year++;
            $periodNumber = 1;
        }

        $period = $fiscalPeriods->create([
            'year' => $year,
            'period_number' => $periodNumber,
            'start_date' => $nextStart->toDateString(),
            'end_date' => $nextStart->copy()->endOfMonth()->toDateString(),
        ]);

        $this->info(sprintf(
            'Opened fiscal period %d-P%d (%s to %s).',
            $period->year,
            $period->period_number,
            $period->start_date->toDateString(),
            $period->end_date->toDateString(),
        ));

        return self::SUCCESS;
    }
}
