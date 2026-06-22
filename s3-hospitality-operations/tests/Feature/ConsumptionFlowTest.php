<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class ConsumptionFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_key_current' => 'test-service-key',
            'services.s2_url' => 'http://s2.test',
        ]);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            'http://s2.test/api/v1/employees/*/deductions' => Http::response([
                'data' => ['id' => 1, 'amount' => '180.00', 'status' => 'applied'],
            ], 201),
        ]);
    }

    public function test_consumption_close_posts_deduction_and_emits_event(): void
    {
        $headers = $this->authHeaders();

        $period = $this->postJson('/api/v1/employee-consumption-periods', [
            'employee_id' => 42,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
            'total_amount' => 180,
        ], $headers)->assertCreated();

        $periodId = $period->json('data.id');

        $this->postJson("/api/v1/employee-consumption-periods/{$periodId}/close", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/deductions')
            && $request->hasHeader('X-Service-Key', 'test-service-key'));

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s3.employee_consumption_period.closed',
        ]);

        $this->assertGreaterThanOrEqual(1, EventOutbox::query()->count());
    }
}
