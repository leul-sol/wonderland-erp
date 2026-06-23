<?php

namespace Database\Seeders;

use App\Support\PermissionCatalogLoader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogPermissionsSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $catalogFiles = [
        's1/permissions.yaml',
        's4/permissions.yaml',
        's3/permissions.yaml',
        's2/permissions.yaml',
    ];

    public function run(): void
    {
        $loader = app(PermissionCatalogLoader::class);

        foreach ($this->catalogFiles as $file) {
            $this->seedCatalog($loader->load($file));
        }
    }

    public function seedFile(string $relativePath): void
    {
        $this->seedCatalog(app(PermissionCatalogLoader::class)->load($relativePath));
    }

    /**
     * @param  array<int, array{action: string, domain: string, display_name: string, roles: array<int, string>}>  $permissions
     */
    private function seedCatalog(array $permissions): void
    {
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
