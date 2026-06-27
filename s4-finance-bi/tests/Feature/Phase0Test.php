<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use App\Models\Payable;
use App\Models\ReportCacheLog;
use App\Support\CoaCatalog;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase0Test extends TestCase
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

    public function test_coa_seeder_loads_accounts_from_yaml_spec(): void
    {
        $expectedCount = count(CoaCatalog::accounts());

        $this->assertDatabaseCount('accounts', $expectedCount);
        $this->assertDatabaseHas('accounts', ['code' => '1001', 'name' => 'Cash and Cash Equivalents']);
        $this->assertDatabaseHas('accounts', ['code' => '5005', 'name' => 'Severance Expense']);
    }

    public function test_journal_reversal_keeps_original_posted_and_creates_mirror_entry(): void
    {
        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Reversal target',
            'source_module' => 'manual',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 100],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'phase0-reverse-target']));

        $originalId = $create->json('data.id');
        $entryNumber = $create->json('data.entry_number');
        $this->postJson("/api/v1/journal-entries/{$originalId}/approve", [], $this->authHeaders())->assertOk();

        $reverse = $this->postJson("/api/v1/journal-entries/{$originalId}/reverse", [
            'reason' => 'Correction',
        ], $this->authHeaders());

        $reverse->assertCreated()
            ->assertJsonPath('data.reversal_of_id', $originalId)
            ->assertJsonPath('data.source_reference', 'reversal_of:'.$entryNumber)
            ->assertJsonPath('data.status', 'posted');

        $this->assertDatabaseHas('journal_entries', [
            'id' => $originalId,
            'status' => 'posted',
        ]);

        $this->postJson("/api/v1/journal-entries/{$originalId}/reverse", [], $this->authHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_STATE');
    }

    public function test_reversal_blocked_when_current_fiscal_period_is_locked(): void
    {
        $currentPeriod = FiscalPeriod::query()->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->firstOrFail();

        $historicalPeriod = FiscalPeriod::query()
            ->where('id', '!=', $currentPeriod->id)
            ->where('status', 'open')
            ->orderBy('start_date')
            ->firstOrFail();

        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Historical posted entry',
            'source_module' => 'manual',
            'entry_date' => $historicalPeriod->start_date->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 50, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 50],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'phase0-lock-reverse']));

        $entryId = $create->json('data.id');
        $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders())->assertOk();

        $currentPeriod->update(['status' => 'locked']);

        $this->postJson("/api/v1/journal-entries/{$entryId}/reverse", [], $this->authHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'UNPROCESSABLE');
    }

    public function test_receivable_records_customer_type_and_ref_from_ar_posting(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Folio charge',
            'source_module' => 's3',
            'source_reference' => 'folio_id:71',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 250, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 250],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'phase0-ar-folio',
        ])->assertCreated();

        $this->assertDatabaseHas('receivables', [
            'source_reference' => 'folio_id:71',
            'customer_type' => 'hotel_guest',
            'customer_ref_id' => 71,
            'status' => 'open',
        ]);
    }

    public function test_payable_supports_partial_status_and_supplier_id(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Goods receipt',
            'source_module' => 's3',
            'source_reference' => 'supplier_id:9',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1200', 'debit' => 500, 'credit' => 0],
                ['account_code' => '2001', 'debit' => 0, 'credit' => 500],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'phase0-ap-gr',
        ])->assertCreated();

        $payable = Payable::query()->firstOrFail();
        $this->assertSame(9, $payable->supplier_id);
        $this->assertSame('open', $payable->status);

        $this->postJson("/api/v1/payables/{$payable->id}/settle", [
            'amount' => 200,
            'payment_method' => 'cash',
        ], $this->authHeaders())->assertOk();

        $payable->refresh();
        $this->assertSame('partial', $payable->status);
        $this->assertSame('300.00', (string) $payable->balance);
    }

    public function test_journal_post_logs_report_cache_invalidation(): void
    {
        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Cache invalidation',
            'source_module' => 'manual',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 10, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 10],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'phase0-cache-log']));

        $entryId = $create->json('data.id');
        $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders())->assertOk();

        $this->assertTrue(
            ReportCacheLog::query()->where('event', 'invalidated')->exists()
        );
    }

    public function test_sdd_rtm_route_alias_is_available(): void
    {
        $this->getJson('/api/v1/rtm', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }
}
