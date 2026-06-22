<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureSeeded extends Command
{
    protected $signature = 'app:ensure-seeded';

    protected $description = 'Run migrations and seed departments when none exist';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (! Schema::hasTable('departments')) {
            $this->comment('departments table not present — skipping seed.');

            return self::SUCCESS;
        }

        if (DB::table('departments')->exists()) {
            $this->comment('Departments already present — skipping seed.');

            return self::SUCCESS;
        }

        if (! class_exists(\Database\Seeders\DatabaseSeeder::class)) {
            $this->warn('No DatabaseSeeder found — skipping seed.');

            return self::SUCCESS;
        }

        $this->warn('No departments found. Running database seeder...');
        $this->call('db:seed', ['--force' => true]);
        $this->info('Workforce seed data loaded.');

        return self::SUCCESS;
    }
}
