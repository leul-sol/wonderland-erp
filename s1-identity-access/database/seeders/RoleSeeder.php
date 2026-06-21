<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Administrator'],
            ['name' => 'general_manager', 'display_name' => 'General Manager'],
            ['name' => 'finance_manager', 'display_name' => 'Finance Manager'],
            ['name' => 'accountant', 'display_name' => 'Accountant'],
            ['name' => 'hr_manager', 'display_name' => 'HR Manager'],
            ['name' => 'payroll_officer', 'display_name' => 'Payroll Officer'],
            ['name' => 'department_head', 'display_name' => 'Department Head'],
            ['name' => 'inventory_manager', 'display_name' => 'Inventory Manager'],
            ['name' => 'restaurant_manager', 'display_name' => 'Restaurant Manager'],
            ['name' => 'receptionist', 'display_name' => 'Receptionist'],
            ['name' => 'cashier', 'display_name' => 'Cashier'],
            ['name' => 'report_viewer', 'display_name' => 'Report Viewer'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
