<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\CatalogPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Console\Command;

class EnsureSeeded extends Command
{
    protected $signature = 'app:ensure-seeded';

    protected $description = 'Run migrations and seed dev data when the admin user is missing';

    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        $this->call(CatalogPermissionsSeeder::class);

        if (User::query()->where('username', 'super.admin')->exists()) {
            $this->call(SuperAdminSeeder::class);
            $this->comment('Admin user present — synced password from SUPER_ADMIN_PASSWORD.');

            return self::SUCCESS;
        }

        $this->warn('No admin user found. Seeding roles, permissions, and super.admin...');
        $this->call('db:seed', ['--force' => true]);
        $this->info('Default login: super.admin / '.env('SUPER_ADMIN_PASSWORD', 'ChangeMeNow!10'));

        return self::SUCCESS;
    }
}
