<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class SeveranceFlowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 88, 'entry_number' => 'JE-00088']], 201),
        ]);
    }

    public function test_severance_calculation_posts_journal_and_emits_outbox_event(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Bereket Alemu',
            'base_salary' => 24000,
            'hire_date' => '2024-01-01',
        ], $headers)->json('data.id');

        $response = $this->postJson("/api/v1/employees/{$employeeId}/severance/calculate", [], $headers);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'calculated')
            ->assertJsonPath('data.s4_journal_entry_id', '88');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/v1/journal-entries')
                && ($body['lines'][0]['account_code'] ?? '') === '5005'
                && ($body['lines'][1]['account_code'] ?? '') === '2100';
        });

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s2.severance.calculated',
        ]);
    }
}
