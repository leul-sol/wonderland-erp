<?php

namespace App\Console\Commands;

use App\Services\Leave\LeaveAccrualService;
use Illuminate\Console\Command;

class RunLeaveAccruals extends Command
{
    protected $signature = 'leave:accrue {--date=}';

    protected $description = 'Run annual leave accrual for employees on their service anniversary';

    public function handle(LeaveAccrualService $accrual): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now();

        $count = $accrual->runForDate($date);
        $this->info("Processed {$count} employee accrual(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
