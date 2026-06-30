<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FiscalPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $startMonth = (int) config('fiscal.year_start_month', 7);
        $today = Carbon::today();
        $currentFyStartYear = $today->month >= $startMonth ? $today->year : $today->year - 1;

        // Seed the current fiscal year and the next one so reservations/journals
        // a few months ahead (e.g. July check-ins while still in June) always have a period.
        foreach ([$currentFyStartYear, $currentFyStartYear + 1] as $fyStartYear) {
            $this->seedFiscalYear($fyStartYear, $startMonth);
        }
    }

    private function seedFiscalYear(int $fyStartYear, int $startMonth): void
    {
        for ($period = 1; $period <= 12; $period++) {
            $month = (($startMonth - 1 + $period - 1) % 12) + 1;
            $year = $fyStartYear + intdiv($startMonth - 1 + $period - 1, 12);
            $start = Carbon::create($year, $month, 1);
            $end = $start->copy()->endOfMonth();

            DB::table('fiscal_periods')->updateOrInsert(
                ['year' => $fyStartYear, 'period_number' => $period],
                [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
