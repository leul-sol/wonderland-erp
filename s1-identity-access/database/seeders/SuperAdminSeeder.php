<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['username' => 'super.admin'],
            [
                'email' => 'super.admin@wonderlandhotel.local',
                'password' => Hash::make((string) config('seeding.super_admin_password')),
                'display_name' => 'Super Administrator',
                'is_active' => true,
                'must_change_password' => (bool) config('seeding.admin_must_change_password'),
                'password_changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $userId = DB::table('users')->where('username', 'super.admin')->value('id');
        $roleId = DB::table('roles')->where('name', 'super_admin')->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $userId, 'role_id' => $roleId],
            ['assigned_at' => now()]
        );
    }
}
