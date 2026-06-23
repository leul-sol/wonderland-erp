<?php

namespace Database\Seeders;

class S3PermissionsSeeder extends CatalogPermissionsSeeder
{
    public function run(): void
    {
        $this->seedFile('s3/permissions.yaml');
    }
}
