<?php

namespace App\Console\Commands;

use App\Services\EmployeeConsumptionService;
use Illuminate\Console\Command;

class PushEmployeeConsumptionToPayroll extends Command
{
    protected $signature = 'consumption:push-to-payroll';

    protected $description = 'Push accumulated employee consumption totals to S2 payroll deductions';

    public function handle(EmployeeConsumptionService $consumption): int
    {
        $count = $consumption->pushPendingToPayroll();
        $this->comment("Pushed {$count} consumption record(s) to payroll.");

        return self::SUCCESS;
    }
}
