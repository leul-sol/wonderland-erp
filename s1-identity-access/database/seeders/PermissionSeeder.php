<?php

namespace Database\Seeders;

class PermissionSeeder extends CatalogPermissionsSeeder
{
    public function run(): void
    {
        $this->seedFile('s1/permissions.yaml');
    }
}
