<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\CatalogPermissionsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Console\Command;

class SyncSuperAdmin extends Command
{
    protected $signature = 'app:sync-super-admin';

    protected $description = 'Reactivate super.admin, ensure roles/permissions, sync password (dev/smoke)';

    public function handle(): int
    {
        $this->call(RoleSeeder::class);
        $this->call(CatalogPermissionsSeeder::class);
        $this->call(SuperAdminSeeder::class);

        $user = User::query()->where('username', 'super.admin')->firstOrFail();
        $user->is_active = true;
        $user->must_change_password = false;
        $user->failed_login_count = 0;
        $user->locked_until = null;
        $user->save();

        $this->info('super.admin synced (active, super_admin role, password from SUPER_ADMIN_PASSWORD).');

        return self::SUCCESS;
    }
}
