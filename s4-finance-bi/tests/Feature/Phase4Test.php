<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase4Test extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_key_current' => 'test-service-key',
            'services.s2_url' => 'http://s2.test',
            'services.s3_url' => 'http://s3.test',
        ]);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);

        Http::fake([
            'http://s2.test/api/v1/employees' => Http::response([
                'data' => [
                    ['id' => 1, 'employee_number' => 'E001', 'status' => 'active'],
                    ['id' => 2, 'employee_number' => 'E002', 'status' => 'archived'],
                ],
            ]),
            'http://s2.test/api/v1/payroll-runs' => Http::response([
                'data' => [
                    ['id' => 10, 'status' => 'approved', 'total_net' => '50000.00'],
                    ['id' => 11, 'status' => 'draft', 'total_net' => '0.00'],
                ],
            ]),
            'http://s3.test/api/v1/rooms' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'occupied'],
                    ['id' => 2, 'status' => 'vacant'],
                    ['id' => 3, 'status' => 'occupied'],
                ],
            ]),
            'http://s3.test/api/v1/reservations' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'checked_in'],
                    ['id' => 2, 'status' => 'checked_out'],
                ],
            ]),
            'http://s3.test/api/v1/orders' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'finalized', 'subtotal' => '700.00'],
                    ['id' => 2, 'status' => 'open', 'subtotal' => '0.00'],
                ],
            ]),
        ]);
    }

    public function test_revenue_by_source_groups_posted_journals(): void
    {
        $this->postPostedJournal('s3', 'folio-1', [
            ['account_code' => '1100', 'debit' => 1000, 'credit' => 0],
            ['account_code' => '4001', 'debit' => 0, 'credit' => 1000],
        ], 'rev-s3-1');

        $this->postPostedJournal('s2', 'payroll-1', [
            ['account_code' => '5001', 'debit' => 500, 'credit' => 0],
            ['account_code' => '2100', 'debit' => 0, 'credit' => 500],
        ], 'rev-s2-1');

        $period = $this->currentFiscalPeriod();
        $response = $this->getJson('/api/v1/bi/reports/revenue-by-source?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.lines.0.source_module', 's2')
            ->assertJsonPath('data.lines.1.source_module', 's3');
    }

    public function test_hospitality_snapshot_reads_s3(): void
    {
        $response = $this->getJson('/api/v1/bi/reports/hospitality-snapshot', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.rooms.total', 3)
            ->assertJsonPath('data.rooms.occupied', 2)
            ->assertJsonPath('data.fb_orders.revenue', '700.00');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Service-Key', 'test-service-key'));
    }

    public function test_payroll_snapshot_reads_s2(): void
    {
        $response = $this->getJson('/api/v1/bi/reports/payroll-snapshot', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.active_employees', 1)
            ->assertJsonPath('data.approved_payroll_runs', 1);
    }

    public function test_operations_dashboard_combines_finance_and_ops(): void
    {
        $this->postPostedJournal('s3', 'folio-2', [
            ['account_code' => '1100', 'debit' => 400, 'credit' => 0],
            ['account_code' => '4002', 'debit' => 0, 'credit' => 400],
        ], 'ops-rev-1');

        $period = $this->currentFiscalPeriod();
        $response = $this->getJson('/api/v1/dashboards/operations?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.dashboard', 'operations')
            ->assertJsonPath('data.finance.revenue', '400.00')
            ->assertJsonPath('data.hospitality.occupancy_rate', '66.67')
            ->assertJsonPath('data.workforce.active_employees', 1);
    }

    public function test_csv_export_returns_downloadable_income_statement(): void
    {
        $this->postPostedJournal('s3', 'folio-3', [
            ['account_code' => '1100', 'debit' => 300, 'credit' => 0],
            ['account_code' => '4001', 'debit' => 0, 'credit' => 300],
        ], 'export-rev-1');

        $period = $this->currentFiscalPeriod();
        $response = $this->postJson('/api/v1/bi/exports', [
            'report' => 'income_statement',
            'format' => 'csv',
            'fiscal_period_id' => $period->id,
        ], $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('net_income', $response->streamedContent());
    }

    public function test_cash_flow_report_returns_cash_balances(): void
    {
        $this->postPostedJournal('s3', 'folio-4', [
            ['account_code' => '1001', 'debit' => 1500, 'credit' => 0],
            ['account_code' => '4001', 'debit' => 0, 'credit' => 1500],
        ], 'cash-rev-1');

        $period = $this->currentFiscalPeriod();
        $response = $this->getJson('/api/v1/reports/cash-flow?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'cash_flow')
            ->assertJsonPath('data.closing_cash', '1500.00');
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function postPostedJournal(string $module, string $reference, array $lines, string $idempotencyKey): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Test journal '.$reference,
            'source_module' => $module,
            'source_reference' => $reference,
            'entry_date' => now()->toDateString(),
            'lines' => $lines,
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => $idempotencyKey,
        ])->assertCreated();
    }

    private function currentFiscalPeriod(): FiscalPeriod
    {
        $today = now()->toDateString();

        return FiscalPeriod::query()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->firstOrFail();
    }
}
