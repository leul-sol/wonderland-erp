<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureSeeded extends Command
{
    protected $signature = 'app:ensure-seeded';

    protected $description = 'Run migrations and seed room catalog when no room types exist';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (! Schema::hasTable('room_types')) {
            $this->comment('room_types table not present — skipping seed.');

            return self::SUCCESS;
        }

        if (DB::table('room_types')->exists()) {
            $this->comment('Room catalog already present — skipping seed.');

            return self::SUCCESS;
        }

        if (! class_exists(\Database\Seeders\DatabaseSeeder::class)) {
            $this->warn('No DatabaseSeeder found — skipping seed.');

            return self::SUCCESS;
        }

        $this->warn('No room types found. Running database seeder...');
        $this->call('db:seed', ['--force' => true]);
        $this->info('Hospitality seed data loaded.');

        return self::SUCCESS;
    }
}
