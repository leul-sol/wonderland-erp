<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use App\Models\OperationalEvent;
use App\Services\EventConsumerService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\BudgetSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase7Test extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'file']);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
            BudgetSeeder::class,
        ]);
    }

    public function test_event_consumer_logs_and_invalidates_s2_cache(): void
    {
        Cache::put('s2.employees', ['cached' => true], 60);

        app(EventConsumerService::class)->handle(
            config('events.channels.leave_approved'),
            [
                'source_system' => 'S2',
                'request_id' => '00000000-0000-0000-0000-000000000001',
                'occurred_at' => now()->toIso8601String(),
                'payload' => ['leave_request_id' => 1],
            ]
        );

        $this->assertDatabaseHas('operational_events', [
            'channel' => config('events.channels.leave_approved'),
            'source_system' => 'S2',
        ]);

        $this->assertNull(Cache::get('s2.employees'));
        $this->assertGreaterThanOrEqual(1, OperationalEvent::query()->count());
    }

    public function test_operational_events_api_lists_recent_events(): void
    {
        OperationalEvent::query()->create([
            'channel' => config('events.channels.folio_settled'),
            'source_system' => 'S3',
            'request_id' => '00000000-0000-0000-0000-000000000002',
            'payload' => ['folio_id' => 9],
            'occurred_at' => now(),
        ]);

        $this->getJson('/api/v1/bi/operational-events', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.0.channel', config('events.channels.folio_settled'));
    }

    public function test_budget_create_and_variance_report(): void
    {
        $period = FiscalPeriod::query()->firstOrFail();

        $this->postJson('/api/v1/finance/budgets', [
            'fiscal_period_id' => $period->id,
            'account_code' => '4010',
            'budget_amount' => 12000,
        ], $this->authHeaders())->assertCreated();

        $response = $this->getJson('/api/v1/bi/reports/budget_variance?fiscal_period_id='.$period->id, $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.report', 'budget_variance')
            ->assertJsonStructure(['data' => ['actual_net_income', 'budget_net_income', 'variance']]);
    }
}
