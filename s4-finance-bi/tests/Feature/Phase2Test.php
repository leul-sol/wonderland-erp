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

    public function test_manual_journal_approve_auto_posts_under_threshold(): void
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
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.approved_by', 1)
            ->assertJsonPath('data.posted_at', fn ($v) => $v !== null);

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s4.journal.posted',
            'status' => 'pending',
        ]);
    }

    public function test_large_manual_journal_requires_general_manager_approval(): void
    {
        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Large adjustment',
            'source_module' => 'manual',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 60000, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 60000],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'manual-adj-large']));

        $entryId = $create->json('data.id');

        $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->putJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders(roles: ['general_manager']))
            ->assertOk()
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.second_approved_by', 1);
    }

    public function test_draft_manual_journal_can_be_deleted_by_creator(): void
    {
        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Discard me',
            'source_module' => 'manual',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 50, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 50],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'draft-delete']));

        $entryId = $create->json('data.id');

        $this->deleteJson("/api/v1/journal-entries/{$entryId}", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('journal_entries', ['id' => $entryId]);
    }

    public function test_closed_period_blocks_posting_approved_journal(): void
    {
        $period = FiscalPeriod::query()->where('status', 'open')->firstOrFail();

        $create = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Pending entry',
            'source_module' => 'manual',
            'entry_date' => $period->start_date->toDateString(),
            'lines' => [
                ['account_code' => '1001', 'debit' => 60000, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 60000],
            ],
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'close-block-1']));

        $entryId = $create->json('data.id');
        $this->postJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders())->assertOk();

        $this->putJson("/api/v1/fiscal-periods/{$period->id}/close", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'closing');

        $this->putJson("/api/v1/fiscal-periods/{$period->id}/close", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $post = $this->putJson("/api/v1/journal-entries/{$entryId}/approve", [], $this->authHeaders(roles: ['general_manager']));
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

    public function test_receivable_write_off(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Folio charge',
            'source_module' => 's3',
            'source_reference' => 'FOLIO-4001',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 500, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 500],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'folio-4001-charge',
        ])->assertCreated();

        $receivable = Receivable::query()->where('source_reference', 'FOLIO-4001')->firstOrFail();

        $this->postJson("/api/v1/receivables/{$receivable->id}/write-off", [
            'reason' => 'Uncollectible',
        ], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'written_off')
            ->assertJsonPath('data.balance', '0.00');
    }

    public function test_fiscal_period_two_step_close_and_lock(): void
    {
        $period = FiscalPeriod::query()->where('status', 'open')->firstOrFail();

        $this->putJson("/api/v1/fiscal-periods/{$period->id}/close", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'closing');

        $this->putJson("/api/v1/fiscal-periods/{$period->id}/close", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.closed_by', 1);

        $this->assertDatabaseHas('account_period_balances', [
            'fiscal_period_id' => $period->id,
        ]);

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s4.period.closed',
        ]);

        $this->putJson("/api/v1/fiscal-periods/{$period->id}/lock", [], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.status', 'locked');
    }
}
