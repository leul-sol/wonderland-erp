<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class LeaveFoundationTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    public function test_employee_create_initialises_leave_balances(): void
    {
        $this->seed(DatabaseSeeder::class);
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Leave Staff',
            'base_salary' => 12000,
        ], $headers)->assertCreated()->json('data.id');

        $response = $this->getJson("/api/v1/employees/{$employeeId}/leave-balances", $headers);

        $response->assertOk();
        $this->assertGreaterThanOrEqual(7, count($response->json('data')));
    }

    public function test_approve_annual_leave_sets_on_leave_status(): void
    {
        $this->seed(DatabaseSeeder::class);
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'On Leave Staff',
            'base_salary' => 12000,
        ], $headers)->json('data.id');

        $leaveId = $this->postJson('/api/v1/leave-requests', [
            'employee_id' => $employeeId,
            'leave_type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-05',
            'days_requested' => 5,
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/leave-requests/{$leaveId}/approve", [], $headers)->assertOk();

        $this->getJson("/api/v1/employees/{$employeeId}", $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'on_leave');
    }
}
