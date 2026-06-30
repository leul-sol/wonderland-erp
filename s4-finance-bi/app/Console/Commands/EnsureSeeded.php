<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureSeeded extends Command
{
    protected $signature = 'app:ensure-seeded';

    protected $description = 'Run migrations and seed finance catalog, RTM, and UAT data when missing';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (! Schema::hasTable('accounts')) {
            $this->comment('Accounts table not present — skipping seed.');

            return self::SUCCESS;
        }

        if (! DB::table('accounts')->exists()) {
            $this->warn('No accounts found. Running database seeder...');
            $this->call('db:seed', ['--force' => true]);
            $this->info('Finance seed data loaded.');

            return self::SUCCESS;
        }

        if (Schema::hasTable('rtm_entries')) {
            $this->call(\Database\Seeders\RtmSeeder::class);
        }

        if (Schema::hasTable('uat_scenarios')) {
            $this->call(\Database\Seeders\UatSeeder::class);
        }

        if (Schema::hasTable('fiscal_periods')) {
            $this->call(\Database\Seeders\FiscalPeriodSeeder::class);
        }

        $this->comment('S4 seed data present.');

        return self::SUCCESS;
    }
}
