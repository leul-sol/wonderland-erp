<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use App\Models\Receivable;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase2Test extends TestCase
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

    public function test_manual_journal_approve_and_post_workflow(): void
    {
        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Manual adjustment',
            'source_module' => 'manual',
            'source_reference' => 'ADJ-001',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 250, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 250],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'manual-adj-001']));

        $create->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $entryId = $create->json('data.id');

        $approve = $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders());
        $approve->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', 1);

        $post = $this->postJson("/api/v1/journal-entries/{$entryId}/post", [], $this->authHeaders());
        $post->assertOk()
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.posted_at', fn ($v) => $v !== null);
    }

    public function test_closed_period_blocks_posting_approved_journal(): void
    {
        $period = FiscalPeriod::query()->where('status', 'open')->firstOrFail();

        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Pending entry',
            'source_module' => 'manual',
            'entry_date' => $period->start_date->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 100],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'close-block-1']));

        $entryId = $create->json('data.id');
        $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders())->assertOk();

        $this->postJson("/api/v1/fiscal-periods/{$period->id}/close", [], $this->authHeaders())->assertOk();

        $post = $this->postJson("/api/v1/journal-entries/{$entryId}/post", [], $this->authHeaders());
        $post->assertStatus(422)
            ->assertJsonPath('error.code', 'UNPROCESSABLE');
    }

    public function test_s3_journal_creates_receivable(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Folio charge',
            'source_module' => 's3',
            'source_reference' => 'FOLIO-2001',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 1500, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 1500],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'folio-2001-charge',
        ])->assertCreated();

        $this->assertDatabaseHas('receivables', [
            'source_reference' => 'FOLIO-2001',
            'source_module' => 's3',
            'balance' => '1500.00',
            'status' => 'open',
        ]);
    }

    public function test_receivable_settle_reduces_balance(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Folio charge',
            'source_module' => 's3',
            'source_reference' => 'FOLIO-3001',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 800, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 800],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'folio-3001-charge',
        ])->assertCreated();

        $receivable = Receivable::query()->where('source_reference', 'FOLIO-3001')->firstOrFail();

        $settle = $this->postJson("/api/v1/receivables/{$receivable->id}/settle", [
            'amount' => 800,
            'payment_method' => 'cash',
        ], $this->authHeaders());

        $settle->assertOk()
            ->assertJsonPath('data.balance', '0.00')
            ->assertJsonPath('data.status', 'settled');
    }

    public function test_fiscal_period_close_and_lock(): void
    {
        $period = FiscalPeriod::query()->where('status', 'open')->firstOrFail();

        $close = $this->postJson("/api/v1/fiscal-periods/{$period->id}/close", [], $this->authHeaders());
        $close->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.closed_by', 1);

        $lock = $this->postJson("/api/v1/fiscal-periods/{$period->id}/lock", [], $this->authHeaders());
        $lock->assertOk()
            ->assertJsonPath('data.status', 'locked');
    }
}
