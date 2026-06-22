<?php

namespace Tests\Feature;

use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalTest extends TestCase
{
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

    public function test_service_key_can_post_balanced_journal_entry(): void
    {
        $response = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Room charge',
            'source_module' => 's3',
            'source_reference' => 'FOLIO-1001',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 1000, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 1000],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'folio-1001-charge',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.source_module', 's3')
            ->assertJsonPath('data.total_debit', '1000.00')
            ->assertJsonPath('data.total_credit', '1000.00');
    }

    public function test_unbalanced_journal_returns_422(): void
    {
        $response = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Bad entry',
            'source_module' => 's3',
            'lines' => [
                ['account_code' => '1100', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 50],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'bad-entry-1',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'UNBALANCED_JOURNAL');
    }

    public function test_idempotency_key_replays_existing_entry(): void
    {
        $payload = [
            'description' => 'Payroll accrual',
            'source_module' => 's2',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '5001', 'debit' => 500, 'credit' => 0],
                ['account_code' => '2100', 'debit' => 0, 'credit' => 500],
            ],
        ];

        $headers = [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'payroll-run-42',
        ];

        $first = $this->postJson('/api/v1/journal-entries', $payload, $headers);
        $first->assertCreated();
        $entryId = $first->json('data.id');

        $second = $this->postJson('/api/v1/journal-entries', $payload, $headers);
        $second->assertOk()
            ->assertJsonPath('data.id', $entryId);
    }

    public function test_automated_entry_requires_idempotency_key(): void
    {
        $response = $this->postJson('/api/v1/journal-entries', [
            'description' => 'Missing key',
            'source_module' => 's2',
            'lines' => [
                ['account_code' => '5001', 'debit' => 100, 'credit' => 0],
                ['account_code' => '2100', 'debit' => 0, 'credit' => 100],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
