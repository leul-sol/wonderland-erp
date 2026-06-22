<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class S4PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['action' => 'S4.finance.accounts.read', 'display_name' => 'Read chart of accounts', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.accounts.create', 'display_name' => 'Create accounts', 'roles' => ['super_admin', 'finance_manager']],
            ['action' => 'S4.finance.accounts.update', 'display_name' => 'Update accounts', 'roles' => ['super_admin', 'finance_manager']],
            ['action' => 'S4.finance.journal_entries.read', 'display_name' => 'Read journal entries', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.journal_entries.create', 'display_name' => 'Create journal entries', 'roles' => ['super_admin', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.journal_entries.approve', 'display_name' => 'Approve journal entries', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S4.finance.journal_entries.reverse', 'display_name' => 'Reverse journal entries', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S4.finance.receivables.read', 'display_name' => 'Read receivables', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.receivables.settle', 'display_name' => 'Settle receivables', 'roles' => ['super_admin', 'finance_manager']],
            ['action' => 'S4.finance.payables.read', 'display_name' => 'Read payables', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.payables.settle', 'display_name' => 'Settle payables', 'roles' => ['super_admin', 'finance_manager']],
            ['action' => 'S4.finance.fiscal_periods.read', 'display_name' => 'Read fiscal periods', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.fiscal_periods.create', 'display_name' => 'Create fiscal periods', 'roles' => ['super_admin', 'finance_manager']],
            ['action' => 'S4.finance.fiscal_periods.close', 'display_name' => 'Close fiscal periods', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S4.finance.fiscal_periods.lock', 'display_name' => 'Lock fiscal periods', 'roles' => ['super_admin', 'general_manager']],
            ['action' => 'S4.finance.reports.read', 'display_name' => 'Read finance reports', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.bi.dashboards.read', 'display_name' => 'Read BI dashboards', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant', 'department_head', 'report_viewer']],
            ['action' => 'S4.bi.reports.read', 'display_name' => 'Read BI reports', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant', 'department_head', 'report_viewer']],
            ['action' => 'S4.bi.export.create', 'display_name' => 'Export BI data', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant', 'department_head']],
            ['action' => 'S4.bi.rtm.read', 'display_name' => 'Read RTM', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S4.bi.rtm.update', 'display_name' => 'Update RTM', 'roles' => ['super_admin', 'general_manager']],
            ['action' => 'S4.bi.uat.read', 'display_name' => 'Read UAT', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S4.bi.uat.update', 'display_name' => 'Update UAT', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S4.finance.budgets.read', 'display_name' => 'Read budget lines', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'accountant']],
            ['action' => 'S4.finance.budgets.create', 'display_name' => 'Create budget lines', 'roles' => ['super_admin', 'finance_manager']],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['action' => $permission['action']],
                [
                    'domain' => 'finance',
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
