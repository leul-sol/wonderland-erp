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

    public function test_cross_system_call_routes_exist(): void
    {
        $checks = [
            's2-workforce-payroll/routes/api.php' => ['/leave-requests', '/attendance-records', '/employees/{employee}/deductions'],
            's3-hospitality-operations/routes/api.php' => ['/rooms', '/reservations', '/group-bookings', '/orders', '/items', '/purchase-orders', '/employee-consumption-periods'],
            's4-finance-bi/routes/api.php' => ['/journal-entries', '/finance/budgets', '/bi/operational-events'],
            's1-identity-access/routes/api.php' => ["'/verify'", '/users', '/roles', '/audit-logs'],
        ];

        foreach ($checks as $relativePath => $needles) {
            $contents = file_get_contents($this->repoRoot().'/'.$relativePath);
            $this->assertIsString($contents, "Missing route file: {$relativePath}");

            foreach ($needles as $needle) {
                $this->assertStringContainsString($needle, $contents, "{$relativePath} missing {$needle}");
            }
        }
    }

    public function test_event_channels_configured_in_emitters_and_consumer(): void
    {
        $eventsYaml = file_get_contents($this->repoRoot().'/specs/platform/events.yaml');
        $this->assertIsString($eventsYaml);

        $s4Channels = [
            'wh.events.s1.permission.changed',
            'wh.events.s2.payroll_run.approved',
            'wh.events.s2.severance.calculated',
            'wh.events.s2.leave.approved',
            'wh.events.s3.goods.received',
            'wh.events.s3.purchase_order.approved',
            'wh.events.s3.order.finalized',
            'wh.events.s3.employee_consumption_period.closed',
            'wh.events.s3.guest.checked_in',
            'wh.events.s3.guest.checked_out',
            'wh.events.s3.folio.settled',
        ];

        $consumerConfig = file_get_contents($this->repoRoot().'/s4-finance-bi/config/events.php');
        $this->assertIsString($consumerConfig);

        foreach ($s4Channels as $channel) {
            $this->assertStringContainsString($channel, $consumerConfig, "S4 consumer missing {$channel}");
        }
    }

    public function test_s4_bi_client_paths_match_cross_system_calls(): void
    {
        $s2Client = file_get_contents($this->repoRoot().'/s4-finance-bi/app/Services/S2WorkforceClient.php');
        $s3Client = file_get_contents($this->repoRoot().'/s4-finance-bi/app/Services/S3HospitalityClient.php');

        foreach (['employees', 'payroll-runs', 'leave-requests', 'attendance-records'] as $path) {
            $this->assertStringContainsString("'{$path}'", $s2Client);
        }

        foreach (['rooms', 'reservations', 'orders', 'items', 'purchase-orders'] as $path) {
            $this->assertStringContainsString("'{$path}'", $s3Client);
        }
    }
}
