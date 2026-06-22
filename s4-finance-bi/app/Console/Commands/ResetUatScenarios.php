<?php

namespace App\Console\Commands;

use App\Models\UatScenario;
use Illuminate\Console\Command;

class ResetUatScenarios extends Command
{
    protected $signature = 'uat:reset-scenarios';

    protected $description = 'Reset all UAT scenario statuses to pending for a fresh E2E run';

    public function handle(): int
    {
        $count = UatScenario::query()->update([
            'status' => 'pending',
            'executed_by' => null,
            'executed_at' => null,
            'notes' => null,
        ]);

        $this->info("Reset {$count} UAT scenario(s) to pending.");

        return self::SUCCESS;
    }
}
