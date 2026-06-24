<?php

namespace Tests\Feature;

use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase7Test extends TestCase
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

    public function test_outbox_publishes_journal_posted_event(): void
    {
        $this->postJson('/api/v1/journal-entries', [
            'description' => 'Automated',
            'source_module' => 's3',
            'source_reference' => 'OUTBOX-1',
            'entry_date' => now()->toDateString(),
            'lines' => [
                ['account_code' => '1100', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4001', 'debit' => 0, 'credit' => 100],
            ],
        ], [
            'X-Service-Key' => 'test-service-key',
            'Idempotency-Key' => 'outbox-journal-1',
        ])->assertCreated();

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s4.journal.posted',
            'status' => 'pending',
        ]);
    }

    public function test_budget_create_and_variance_report(): void
    {
        $period = \App\Models\FiscalPeriod::query()->where('status', 'open')->firstOrFail();

        $this->postJson('/api/v1/finance/budgets', [
            'fiscal_period_id' => $period->id,
            'account_code' => '4001',
            'budget_amount' => 12000,
        ], $this->authHeaders())->assertCreated();

        $response = $this->getJson('/api/v1/bi/reports/budget_variance?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'budget_variance')
            ->assertJsonStructure(['data' => ['actual_net_income', 'budget_net_income', 'variance']]);
    }
}
