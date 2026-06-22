<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase6Test extends TestCase
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
                    ['id' => 1, 'status' => 'active', 'department' => ['name' => 'Front Office']],
                ],
            ]),
            'http://s2.test/api/v1/payroll-runs' => Http::response(['data' => []]),
            'http://s2.test/api/v1/leave-requests' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'approved', 'leave_type' => 'annual'],
                    ['id' => 2, 'status' => 'pending', 'leave_type' => 'sick'],
                ],
            ]),
            'http://s3.test/api/v1/rooms' => Http::response(['data' => []]),
            'http://s3.test/api/v1/reservations' => Http::response(['data' => []]),
            'http://s3.test/api/v1/orders' => Http::response(['data' => []]),
            'http://s3.test/api/v1/items' => Http::response(['data' => []]),
            'http://s3.test/api/v1/purchase-orders' => Http::response(['data' => []]),
        ]);
    }

    public function test_account_create_and_update(): void
    {
        $headers = $this->authHeaders();

        $created = $this->postJson('/api/v1/accounts', [
            'code' => '5999',
            'name' => 'Miscellaneous Expense',
            'type' => 'expense',
            'normal_balance' => 'debit',
        ], $headers);

        $created->assertCreated()
            ->assertJsonPath('data.code', '5999')
            ->assertJsonPath('data.type', 'expense');

        $accountId = $created->json('data.id');

        $this->patchJson("/api/v1/accounts/{$accountId}", [
            'name' => 'Misc Expense Adjusted',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.name', 'Misc Expense Adjusted');
    }

    public function test_fiscal_period_create(): void
    {
        $response = $this->postJson('/api/v1/fiscal-periods', [
            'year' => 2027,
            'period_number' => 1,
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.year', 2027)
            ->assertJsonPath('data.period_number', 1)
            ->assertJsonPath('data.status', 'open');
    }

    public function test_report_catalog_lists_twenty_four_reports(): void
    {
        $response = $this->getJson('/api/v1/bi/reports', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.total', 24)
            ->assertJsonCount(24, 'data.reports');
    }

    public function test_report_catalog_run_by_slug(): void
    {
        $response = $this->getJson('/api/v1/bi/reports/leave_summary', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.slug', 'leave_summary')
            ->assertJsonPath('data.total_requests', 2)
            ->assertJsonPath('data.by_status.approved', 1);
    }

    public function test_pdf_export_returns_downloadable_trial_balance(): void
    {
        $this->postPostedJournal('s3', 'folio-pdf-1', [
            ['account_code' => '1100', 'debit' => 200, 'credit' => 0],
            ['account_code' => '4001', 'debit' => 0, 'credit' => 200],
        ], 'pdf-rev-1');

        $period = $this->currentFiscalPeriod();
        $response = $this->postJson('/api/v1/bi/exports', [
            'report' => 'trial_balance',
            'format' => 'pdf',
            'fiscal_period_id' => $period->id,
        ], $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
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
