<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class EnsureSeeded extends Command
{
    protected $signature = 'app:ensure-seeded';

    protected $description = 'Run migrations and seed dev data when the admin user is missing';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        $this->call(\Database\Seeders\S4PermissionsSeeder::class);

        if (User::query()->where('username', 'super.admin')->exists()) {
            $this->comment('Admin user already present — skipping seed.');

            return self::SUCCESS;
        }

        $this->warn('No admin user found. Seeding roles, permissions, and super.admin...');
        $this->call('db:seed', ['--force' => true]);
        $this->info('Default login: super.admin / ChangeMeNow!10');

        return self::SUCCESS;
    }
}
