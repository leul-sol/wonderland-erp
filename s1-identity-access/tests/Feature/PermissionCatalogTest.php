<?php

namespace Tests\Feature;

use App\Support\PermissionCatalogLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_yaml_catalogs_seed_expected_permission_count(): void
    {
        $this->seed();

        $loader = app(PermissionCatalogLoader::class);
        $expected = 0;

        foreach (['s1/permissions.yaml', 's2/permissions.yaml', 's3/permissions.yaml', 's4/permissions.yaml'] as $file) {
            $expected += count($loader->load($file));
        }

        $this->assertDatabaseCount('permissions', $expected);
        $this->assertSame(112, $expected);
    }
}
