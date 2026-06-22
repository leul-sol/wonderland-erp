<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class SeveranceFlowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_severance_calculation_emits_outbox_event(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Bereket Alemu',
            'base_salary' => 24000,
            'hire_date' => '2024-01-01',
        ], $headers)->json('data.id');

        $response = $this->postJson("/api/v1/employees/{$employeeId}/severance/calculate", [], $headers);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'calculated');

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s2.severance.calculated',
        ]);

        $this->assertGreaterThanOrEqual(1, EventOutbox::query()->count());
    }
}
