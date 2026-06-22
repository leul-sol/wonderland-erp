<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureSeeded extends Command
{
    protected $signature = 'app:ensure-seeded';

    protected $description = 'Run migrations and seed chart of accounts when no accounts exist';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (! Schema::hasTable('accounts')) {
            $this->comment('Accounts table not present — skipping seed.');

            return self::SUCCESS;
        }

        if (DB::table('accounts')->exists()) {
            $this->comment('Accounts already present — skipping seed.');

            return self::SUCCESS;
        }

        if (! class_exists(\Database\Seeders\DatabaseSeeder::class)) {
            $this->warn('No DatabaseSeeder found — skipping seed.');

            return self::SUCCESS;
        }

        $this->warn('No accounts found. Running database seeder...');
        $this->call('db:seed', ['--force' => true]);
        $this->info('Chart of accounts seeded.');

        return self::SUCCESS;
    }
}
