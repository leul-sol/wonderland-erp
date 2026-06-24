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
            's4-finance-bi/routes/api.php' => ['/journal-entries', '/finance/budgets', '/reports/departmental', '/dashboard/finance'],
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

    public function test_s4_outbox_channels_configured(): void
    {
        $eventsYaml = file_get_contents($this->repoRoot().'/specs/platform/events.yaml');
        $this->assertIsString($eventsYaml);

        foreach ([
            'wh.events.s4.journal.posted',
            'wh.events.s4.period.closed',
        ] as $channel) {
            $this->assertStringContainsString($channel, $eventsYaml, "events.yaml missing {$channel}");
        }

        $s4Config = file_get_contents($this->repoRoot().'/s4-finance-bi/config/events.php');
        $this->assertIsString($s4Config);
        $this->assertStringContainsString('journal_posted', $s4Config);
        $this->assertStringContainsString('period_closed', $s4Config);
    }

    public function test_s4_bi_client_paths_match_cross_system_calls(): void
    {
        $s1Client = file_get_contents($this->repoRoot().'/s4-finance-bi/app/Services/S1IdentityClient.php');
        $s2Client = file_get_contents($this->repoRoot().'/s4-finance-bi/app/Services/S2WorkforceClient.php');
        $s3Client = file_get_contents($this->repoRoot().'/s4-finance-bi/app/Services/S3HospitalityClient.php');

        foreach (['users', 'roles', 'audit-logs'] as $path) {
            $this->assertStringContainsString("'{$path}'", $s1Client);
        }

        foreach (['employees', 'payroll-runs', 'leave-requests', 'attendance-records'] as $path) {
            $this->assertStringContainsString("'{$path}'", $s2Client);
        }

        foreach (['rooms', 'reservations', 'orders', 'items', 'purchase-orders'] as $path) {
            $this->assertStringContainsString("'{$path}'", $s3Client);
        }
    }
}
