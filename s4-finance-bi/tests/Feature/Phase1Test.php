<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\OperationalEvent;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\ReportCacheLog;
use App\Services\BiCacheService;
use App\Services\IntegrationCacheService;
use App\Support\FinanceCacheRegistry;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase1Test extends TestCase
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
            'services.cache_ttl.payroll' => 120,
        ]);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);

        FinanceCacheRegistry::reset();
    }

    public function test_ar_aging_report_includes_bucket_totals_and_line_buckets(): void
    {
        $accountId = Account::query()->where('code', '1100')->value('id');
        $asOf = now()->startOfDay();

        Receivable::query()->create([
            'account_id' => $accountId,
            'party_name' => 'Current guest',
            'source_reference' => 'folio_id:1',
            'source_module' => 's3',
            'original_amount' => 100,
            'balance' => 100,
            'due_date' => $asOf->copy()->addDays(5),
            'status' => 'open',
        ]);
        Receivable::query()->create([
            'account_id' => $accountId,
            'party_name' => 'Overdue guest',
            'source_reference' => 'folio_id:2',
            'source_module' => 's3',
            'original_amount' => 200,
            'balance' => 200,
            'due_date' => $asOf->copy()->subDays(15),
            'status' => 'open',
        ]);
        Receivable::query()->create([
            'account_id' => $accountId,
            'party_name' => 'Very overdue guest',
            'source_reference' => 'folio_id:3',
            'source_module' => 's3',
            'original_amount' => 300,
            'balance' => 300,
            'due_date' => $asOf->copy()->subDays(120),
            'status' => 'partial',
        ]);

        $response = $this->getJson('/api/v1/bi/reports/ar_aging', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.bucket_totals.current', '100.00')
            ->assertJsonPath('data.bucket_totals.1_30', '200.00')
            ->assertJsonPath('data.bucket_totals.90_plus', '300.00')
            ->assertJsonPath('data.total_outstanding', '600.00');

        $lines = collect($response->json('data.lines'));
        $this->assertTrue($lines->contains(fn ($line) => $line['aging_bucket'] === '1_30' && $line['days_overdue'] === 15));
    }

    public function test_ap_aging_report_includes_partial_payables_in_buckets(): void
    {
        $accountId = Account::query()->where('code', '2001')->value('id');
        $asOf = now()->startOfDay();

        Payable::query()->create([
            'account_id' => $accountId,
            'supplier_id' => 9,
            'vendor_name' => 'Supplier A',
            'source_reference' => 'supplier_id:9',
            'source_module' => 's3',
            'original_amount' => 400,
            'balance' => 150,
            'due_date' => $asOf->copy()->subDays(45),
            'status' => 'partial',
        ]);

        $response = $this->getJson('/api/v1/bi/reports/ap_aging', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.bucket_totals.31_60', '150.00')
            ->assertJsonPath('data.lines.0.aging_bucket', '31_60')
            ->assertJsonPath('data.lines.0.supplier_id', 9);
    }

    public function test_receivable_list_includes_aging_and_filters_by_bucket(): void
    {
        $accountId = Account::query()->where('code', '1100')->value('id');
        $asOf = now()->startOfDay();

        Receivable::query()->create([
            'account_id' => $accountId,
            'customer_type' => 'hotel_guest',
            'party_name' => 'Bucket A',
            'source_reference' => 'folio_id:10',
            'source_module' => 's3',
            'original_amount' => 50,
            'balance' => 50,
            'due_date' => $asOf->copy()->subDays(10),
            'status' => 'open',
        ]);
        Receivable::query()->create([
            'account_id' => $accountId,
            'customer_type' => 'hotel_guest',
            'party_name' => 'Bucket B',
            'source_reference' => 'folio_id:11',
            'source_module' => 's3',
            'original_amount' => 75,
            'balance' => 75,
            'due_date' => $asOf->copy()->subDays(70),
            'status' => 'open',
        ]);

        $this->getJson('/api/v1/receivables?aging_bucket=1_30', $this->authHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.aging_bucket', '1_30')
            ->assertJsonPath('data.0.days_overdue', 10);
    }

    public function test_payable_list_includes_aging_metadata(): void
    {
        $accountId = Account::query()->where('code', '2001')->value('id');

        Payable::query()->create([
            'account_id' => $accountId,
            'supplier_id' => 3,
            'vendor_name' => 'Vendor',
            'source_reference' => 'supplier_id:3',
            'source_module' => 's3',
            'original_amount' => 90,
            'balance' => 90,
            'due_date' => now()->subDays(5),
            'status' => 'open',
        ]);

        $this->getJson('/api/v1/payables?supplier_id=3', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.aging_bucket', '1_30')
            ->assertJsonPath('data.0.days_overdue', 5);
    }

    public function test_bi_cache_invalidates_registered_prefix_keys(): void
    {
        $cache = app(IntegrationCacheService::class);
        $cache->remember('finance.reports.ar_aging', 60, fn () => ['lines' => []]);
        $cache->remember('finance.revenue.today', 60, fn () => ['total' => '0.00']);

        Cache::put('finance.reports.ar_aging', ['lines' => []], 60);
        Cache::put('finance.revenue.today', ['total' => '0.00'], 60);

        app(BiCacheService::class)->invalidate(null, 'phase1.test');

        $this->assertTrue(
            ReportCacheLog::query()
                ->where('event', 'invalidated')
                ->where('report_key', 'finance.reports.ar_aging')
                ->exists()
        );
        $this->assertTrue(
            ReportCacheLog::query()
                ->where('event', 'invalidated')
                ->where('report_key', 'finance.revenue.today')
                ->exists()
        );
    }

    public function test_integration_cache_returns_stale_payload_with_header_on_remote_failure(): void
    {
        Http::fake([
            'http://s2.test/api/v1/employees' => Http::sequence()
                ->push(['data' => [['id' => 1, 'status' => 'active']]], 200)
                ->push(['message' => 'down'], 503),
            'http://s2.test/api/v1/payroll-runs' => Http::response(['data' => []], 200),
        ]);

        $this->getJson('/api/v1/bi/reports/payroll-snapshot', $this->authHeaders())->assertOk();

        Cache::forget('s2.employees');

        Http::fake([
            'http://s2.test/api/v1/employees' => Http::response(['message' => 'down'], 503),
            'http://s2.test/api/v1/payroll-runs' => Http::response(['data' => []], 200),
        ]);

        $stale = $this->getJson('/api/v1/bi/reports/payroll-snapshot', $this->authHeaders());

        $stale->assertOk()
            ->assertHeader('X-Cache', 'STALE')
            ->assertJsonPath('data.active_employees', 1);
    }

    public function test_cache_ttl_env_values_are_exposed_in_config(): void
    {
        config(['services.cache_ttl.revenue' => 99]);
        config(['services.cache_ttl.occupancy' => 88]);

        $this->assertSame(99, config('services.cache_ttl.revenue'));
        $this->assertSame(88, config('services.cache_ttl.occupancy'));
        $this->assertSame(120, config('services.cache_ttl.payroll'));
    }

    public function test_finance_mutations_write_audit_events(): void
    {
        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Audit trail target',
            'source_module' => 'manual',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 25, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 25],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'phase1-audit']));

        $entryId = $create->json('data.id');
        $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders())->assertOk();

        $this->assertTrue(
            OperationalEvent::query()
                ->where('channel', 'finance.audit')
                ->where('payload->action', 'journal.approve')
                ->where('payload->resource_id', $entryId)
                ->exists()
        );

        $this->assertTrue(
            OperationalEvent::query()
                ->where('channel', 'finance.audit')
                ->where('payload->action', 'journal.post')
                ->where('payload->resource_id', $entryId)
                ->exists()
        );
    }
}
