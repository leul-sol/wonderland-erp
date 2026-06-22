<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class LeaveFlowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_leave_create_and_approve_emits_outbox_event(): void
    {
        $headers = $this->authHeaders();

        $employee = $this->postJson('/api/v1/employees', [
            'full_name' => 'Selam Bekele',
            'base_salary' => 12000,
            'default_role' => 'cashier',
        ], $headers)->assertCreated();

        $employeeId = $employee->json('data.id');

        $created = $this->postJson('/api/v1/leave-requests', [
            'employee_id' => $employeeId,
            'leave_type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'reason' => 'Family visit',
        ], $headers);

        $created->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.days_requested', 5);

        $leaveId = $created->json('data.id');

        $approved = $this->postJson("/api/v1/leave-requests/{$leaveId}/approve", [], $headers);

        $approved->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approved_by', 1);

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s2.leave.approved',
        ]);

        $this->assertGreaterThanOrEqual(2, EventOutbox::query()->count());
    }

    public function test_leave_reject_updates_status(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Hana Girma',
            'base_salary' => 10000,
        ], $headers)->json('data.id');

        $leaveId = $this->postJson('/api/v1/leave-requests', [
            'employee_id' => $employeeId,
            'leave_type' => 'sick',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/leave-requests/{$leaveId}/reject", [
            'reason' => 'Insufficient coverage',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Insufficient coverage');
    }

    public function test_leave_index_lists_requests(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Yonas Alem',
            'base_salary' => 9000,
        ], $headers)->json('data.id');

        $this->postJson('/api/v1/leave-requests', [
            'employee_id' => $employeeId,
            'leave_type' => 'annual',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/leave-requests', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
