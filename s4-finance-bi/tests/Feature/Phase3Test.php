<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase3Test extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);
    }

    public function test_income_statement_reflects_posted_revenue_and_expense(): void
    {
        $this->postPostedJournal('s3', 'folio-room-1', [
            ['account_code' => '1100', 'debit' => 2500, 'credit' => 0],
            ['account_code' => '4001', 'debit' => 0, 'credit' => 2500],
        ], 'room-revenue-1');

        $this->postPostedJournal('s3', 'order-cogs-1', [
            ['account_code' => '5003', 'debit' => 600, 'credit' => 0],
            ['account_code' => '1200', 'debit' => 0, 'credit' => 600],
        ], 'fb-cogs-1');

        $period = $this->currentFiscalPeriod();

        $response = $this->getJson('/api/v1/reports/income-statement?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.revenue.total', '2500.00')
            ->assertJsonPath('data.expenses.total', '600.00')
            ->assertJsonPath('data.net_income', '1900.00');
    }

    public function test_trial_balance_debits_equal_credits(): void
    {
        $this->postPostedJournal('s3', 'folio-room-2', [
            ['account_code' => '1100', 'debit' => 1000, 'credit' => 0],
            ['account_code' => '4001', 'debit' => 0, 'credit' => 1000],
        ], 'room-revenue-2');

        $this->postPostedJournal('s3', 'folio-settle-2', [
            ['account_code' => '1001', 'debit' => 1000, 'credit' => 0],
            ['account_code' => '1100', 'debit' => 0, 'credit' => 1000],
        ], 'folio-settle-2');

        $period = $this->currentFiscalPeriod();
        $response = $this->getJson('/api/v1/reports/trial-balance?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk();

        $totals = $response->json('data.totals');
        $this->assertSame($totals['debit'], $totals['credit']);
    }

    public function test_balance_sheet_lists_assets_and_liabilities(): void
    {
        $this->postPostedJournal('s3', 'po-recv-1', [
            ['account_code' => '1200', 'debit' => 3000, 'credit' => 0],
            ['account_code' => '2001', 'debit' => 0, 'credit' => 3000],
        ], 'goods-recv-1');

        $period = $this->currentFiscalPeriod();
        $response = $this->getJson('/api/v1/reports/balance-sheet?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.assets.total', '3000.00')
            ->assertJsonPath('data.liabilities.total', '3000.00')
            ->assertJsonPath('data.equity.total', '0.00');
    }

    public function test_executive_dashboard_returns_kpis(): void
    {
        $this->postPostedJournal('s3', 'folio-room-3', [
            ['account_code' => '1100', 'debit' => 800, 'credit' => 0],
            ['account_code' => '4002', 'debit' => 0, 'credit' => 800],
        ], 'fb-revenue-3');

        $period = $this->currentFiscalPeriod();
        $response = $this->getJson('/api/v1/dashboards/executive?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.dashboard', 'executive')
            ->assertJsonPath('data.kpis.revenue', '800.00')
            ->assertJsonPath('data.kpis.net_income', '800.00')
            ->assertJsonStructure(['data' => ['kpis' => ['cash_position', 'ar_outstanding', 'ap_outstanding']]]);
    }

    public function test_reports_require_permission(): void
    {
        $this->getJson('/api/v1/reports/income-statement', $this->authHeaders([
            'S4.finance.journal_entries.read',
        ]))->assertStatus(403);
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
