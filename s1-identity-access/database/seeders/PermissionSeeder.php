<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['domain' => 'identity', 'action' => 'S1.identity.users.read', 'display_name' => 'Read users', 'roles' => ['super_admin', 'general_manager', 'report_viewer']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.create', 'display_name' => 'Create users', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.update', 'display_name' => 'Update users', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.delete', 'display_name' => 'Delete users', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.deactivate', 'display_name' => 'Deactivate users', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.force_logout', 'display_name' => 'Force logout users', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.reset_password', 'display_name' => 'Reset user passwords', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.users.assign_role', 'display_name' => 'Assign user roles', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.roles.read', 'display_name' => 'Read roles', 'roles' => ['super_admin', 'general_manager', 'report_viewer']],
            ['domain' => 'identity', 'action' => 'S1.identity.roles.create', 'display_name' => 'Create roles', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.roles.update', 'display_name' => 'Update roles', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.roles.delete', 'display_name' => 'Delete roles', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.roles.sync_permissions', 'display_name' => 'Sync role permissions', 'roles' => ['super_admin']],
            ['domain' => 'identity', 'action' => 'S1.identity.permissions.read', 'display_name' => 'Read permissions', 'roles' => ['super_admin', 'general_manager', 'report_viewer']],
            ['domain' => 'identity', 'action' => 'S1.identity.audit_logs.read', 'display_name' => 'Read audit logs', 'roles' => ['super_admin', 'general_manager', 'report_viewer']],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['action' => $permission['action']],
                [
                    'domain' => $permission['domain'],
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
