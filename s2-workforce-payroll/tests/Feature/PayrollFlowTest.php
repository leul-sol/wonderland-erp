<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class PayrollFlowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 42, 'entry_number' => 'JE-00010']], 201),
        ]);
    }

    public function test_employee_create_emits_outbox_event(): void
    {
        $response = $this->postJson('/api/v1/employees', [
            'full_name' => 'Dawit Haile',
            'job_title' => 'Accountant',
            'base_salary' => 15000,
            'default_role' => 'accountant',
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s2.employee.created',
        ]);
    }

    public function test_payroll_approve_posts_journal_to_s4(): void
    {
        $headers = $this->authHeaders();

        $this->postJson('/api/v1/employees', [
            'full_name' => 'Marta Tadesse',
            'base_salary' => 20000,
            'default_role' => 'cashier',
        ], $headers)->assertCreated();

        $run = $this->postJson('/api/v1/payroll-runs', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ], $headers);

        $run->assertCreated();
        $runId = $run->json('data.id');

        $approved = $this->postJson("/api/v1/payroll-runs/{$runId}/approve", [], $headers);

        $approved->assertOk()
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.s4_journal_entry_id', '42');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/journal-entries')
            && $request->header('X-Service-Key') !== []
            && $request->header('Idempotency-Key') !== []);

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s2.payroll_run.approved',
        ]);

        $this->assertGreaterThanOrEqual(2, EventOutbox::query()->count());
    }
}
