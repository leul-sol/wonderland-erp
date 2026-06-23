<?php

namespace Tests\Contract;

use Tests\TestCase;

class PlatformContractTest extends TestCase
{
    public function test_s1_auth_and_admin_routes_exist(): void
    {
        $contents = file_get_contents(base_path('routes/api.php'));
        $this->assertIsString($contents);

        foreach (["'/login'", "'/verify'", "'/users'", "'/roles'", "'/permissions'", "'/audit-logs'", "'/openapi.json'"] as $needle) {
            $this->assertStringContainsString($needle, $contents);
        }
    }

    public function test_permission_yaml_catalogs_exist_for_all_systems(): void
    {
        foreach (['s1', 's2', 's3', 's4'] as $system) {
            $path = base_path("specs/{$system}/permissions.yaml");
            $this->assertFileExists($path, "Missing {$path}");
            $this->assertStringContainsString('permissions:', file_get_contents($path));
        }
    }

    public function test_s2_employee_events_configured(): void
    {
        $config = file_get_contents(base_path('config/events.php'));
        $this->assertIsString($config);

        foreach ([
            'wh.events.s2.employee.created',
            'wh.events.s2.employee.updated',
            'wh.events.s2.employee.archived',
        ] as $channel) {
            $this->assertStringContainsString($channel, $config);
        }
    }
}
