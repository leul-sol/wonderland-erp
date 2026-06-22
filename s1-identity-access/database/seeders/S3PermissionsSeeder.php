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
            ['action' => 'S3.hospitality.items.read', 'display_name' => 'Read inventory items', 'roles' => ['super_admin', 'general_manager', 'inventory_manager', 'restaurant_manager', 'cashier', 'report_viewer']],
            ['action' => 'S3.hospitality.purchase_orders.read', 'display_name' => 'Read purchase orders', 'roles' => ['super_admin', 'general_manager', 'finance_manager', 'inventory_manager', 'report_viewer']],
            ['action' => 'S3.hospitality.purchase_orders.create', 'display_name' => 'Create purchase orders', 'roles' => ['super_admin', 'inventory_manager']],
            ['action' => 'S3.hospitality.purchase_orders.approve', 'display_name' => 'Approve purchase orders', 'roles' => ['super_admin', 'general_manager', 'finance_manager']],
            ['action' => 'S3.hospitality.purchase_orders.receive', 'display_name' => 'Receive purchase orders', 'roles' => ['super_admin', 'inventory_manager']],
            ['action' => 'S3.hospitality.menu_items.read', 'display_name' => 'Read menu items', 'roles' => ['super_admin', 'restaurant_manager', 'cashier', 'receptionist', 'report_viewer']],
            ['action' => 'S3.hospitality.orders.read', 'display_name' => 'Read restaurant orders', 'roles' => ['super_admin', 'restaurant_manager', 'cashier', 'receptionist', 'report_viewer']],
            ['action' => 'S3.hospitality.orders.create', 'display_name' => 'Create restaurant orders', 'roles' => ['super_admin', 'restaurant_manager', 'cashier']],
            ['action' => 'S3.hospitality.orders.finalize', 'display_name' => 'Finalize restaurant orders', 'roles' => ['super_admin', 'restaurant_manager', 'cashier']],
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
