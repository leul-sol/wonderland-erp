<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            S4PermissionsSeeder::class,
            S3PermissionsSeeder::class,
            S2PermissionsSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
