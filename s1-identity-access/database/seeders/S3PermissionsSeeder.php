<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class S3PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['action' => 'S3.hospitality.rooms.read', 'display_name' => 'Read rooms', 'roles' => ['super_admin', 'general_manager', 'receptionist', 'cashier', 'report_viewer']],
            ['action' => 'S3.hospitality.reservations.read', 'display_name' => 'Read reservations', 'roles' => ['super_admin', 'general_manager', 'receptionist', 'cashier', 'report_viewer']],
            ['action' => 'S3.hospitality.reservations.create', 'display_name' => 'Create reservations', 'roles' => ['super_admin', 'receptionist', 'cashier']],
            ['action' => 'S3.hospitality.reservations.check_in', 'display_name' => 'Check in guests', 'roles' => ['super_admin', 'receptionist']],
            ['action' => 'S3.hospitality.reservations.check_out', 'display_name' => 'Check out guests', 'roles' => ['super_admin', 'receptionist', 'cashier']],
            ['action' => 'S3.hospitality.folios.read', 'display_name' => 'Read folios', 'roles' => ['super_admin', 'general_manager', 'receptionist', 'cashier', 'finance_manager']],
            ['action' => 'S3.hospitality.folios.charge', 'display_name' => 'Post folio charges', 'roles' => ['super_admin', 'receptionist', 'cashier']],
            ['action' => 'S3.hospitality.folios.settle', 'display_name' => 'Settle folios', 'roles' => ['super_admin', 'cashier']],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['action' => $permission['action']],
                [
                    'domain' => 'hospitality',
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
