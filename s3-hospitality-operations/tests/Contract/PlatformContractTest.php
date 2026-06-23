<?php

namespace Tests\Contract;

use Tests\TestCase;

class PlatformContractTest extends TestCase
{
    private function repoRoot(): string
    {
        $configured = getenv('WONDERLAND_REPO_ROOT');

        if (is_string($configured) && $configured !== '' && is_dir($configured)) {
            return rtrim($configured, '/\\');
        }

        return dirname(base_path(), 2);
    }

    public function test_s3_routes_match_cross_system_surface(): void
    {
        $routes = file_get_contents(base_path('routes/api.php'));
        $this->assertIsString($routes);

        foreach ([
            '/rooms',
            '/reservations',
            '/group-bookings',
            '/folios/{folio}',
            '/items',
            '/purchase-orders',
            '/orders',
            '/employee-consumption-periods',
            '/item-categories',
            '/suppliers',
            '/guest-profiles',
            '/cashier-shifts',
            '/bills/{bill}',
        ] as $needle) {
            $this->assertStringContainsString($needle, $routes);
        }
    }

    public function test_s3_permission_yaml_matches_sdd_domains(): void
    {
        $yaml = file_get_contents($this->repoRoot().'/specs/s3/permissions.yaml');
        $this->assertIsString($yaml);

        foreach ([
            'S3.inventory.items.read',
            'S3.restaurant.menu.read',
            'S3.hotel.folios.write',
            'S3.hotel.group_bookings.read',
        ] as $permission) {
            $this->assertStringContainsString($permission, $yaml);
        }
    }

    public function test_s3_outbox_channels_match_platform_events(): void
    {
        $config = file_get_contents(base_path('config/events.php'));
        $this->assertIsString($config);

        foreach ([
            'wh.events.s3.goods.received',
            'wh.events.s3.purchase_order.approved',
            'wh.events.s3.purchase_order.cancelled',
            'wh.events.s3.order.finalized',
            'wh.events.s3.employee_consumption_period.closed',
            'wh.events.s3.stock.expiry_alert',
            'wh.events.s3.employee_consumption.pushed',
            'wh.events.s3.guest.checked_in',
            'wh.events.s3.guest.checked_out',
            'wh.events.s3.folio.settled',
        ] as $channel) {
            $this->assertStringContainsString($channel, $config);
        }
    }

    public function test_s2_deduction_client_path_exists(): void
    {
        $client = file_get_contents(base_path('app/Services/S2WorkforceClient.php'));
        $this->assertIsString($client);
        $this->assertStringContainsString('/api/v1/employees/', $client);
        $this->assertStringContainsString('/deductions', $client);
        $this->assertStringContainsString('Idempotency-Key', $client);
    }

    public function test_service_key_bypasses_permission_middleware(): void
    {
        $middleware = file_get_contents(base_path('app/Http/Middleware/CheckPermission.php'));
        $jwt = file_get_contents(base_path('app/Http/Middleware/JwtAuthenticate.php'));
        $this->assertIsString($middleware);
        $this->assertIsString($jwt);
        $this->assertStringContainsString('auth_via_service_key', $middleware);
        $this->assertStringContainsString('auth_via_service_key', $jwt);
    }
}
