<?php

namespace Database\Seeders;

class S4PermissionsSeeder extends CatalogPermissionsSeeder
{
    public function run(): void
    {
        $this->seedFile('s4/permissions.yaml');
    }
}
