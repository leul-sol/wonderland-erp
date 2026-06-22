<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class S2PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['action' => 'S2.workforce.employees.read', 'display_name' => 'Read employees', 'roles' => ['super_admin', 'general_manager', 'hr_manager', 'payroll_officer', 'report_viewer']],
            ['action' => 'S2.workforce.employees.create', 'display_name' => 'Create employees', 'roles' => ['super_admin', 'hr_manager']],
            ['action' => 'S2.workforce.employees.update', 'display_name' => 'Update employees', 'roles' => ['super_admin', 'hr_manager']],
            ['action' => 'S2.workforce.employees.archive', 'display_name' => 'Archive employees', 'roles' => ['super_admin', 'hr_manager']],
            ['action' => 'S2.workforce.payroll_runs.read', 'display_name' => 'Read payroll runs', 'roles' => ['super_admin', 'general_manager', 'hr_manager', 'payroll_officer']],
            ['action' => 'S2.workforce.payroll_runs.create', 'display_name' => 'Create payroll runs', 'roles' => ['super_admin', 'payroll_officer']],
            ['action' => 'S2.workforce.payroll_runs.approve', 'display_name' => 'Approve payroll runs', 'roles' => ['super_admin', 'payroll_officer']],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['action' => $permission['action']],
                [
                    'domain' => 'workforce',
                    'display_name' => $permission['display_name'],
                    'created_at' => now(),
                ]
            );

            $permissionId = DB::table('permissions')->where('action', $permission['action'])->value('id');

            foreach ($permission['roles'] as $roleName) {
                $roleId = DB::table('roles')->where('name', $roleName)->value('id');

                if ($roleId === null) {
                    continue;
                }

                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['granted_at' => now()]
                );
            }
        }
    }
}
