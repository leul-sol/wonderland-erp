<?php

namespace Database\Seeders;

class S2PermissionsSeeder extends CatalogPermissionsSeeder
{
    public function run(): void
    {
        $this->seedFile('s2/permissions.yaml');
    }
}
