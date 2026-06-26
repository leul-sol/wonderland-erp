<?php

namespace Tests\Feature;

use App\Models\OvertimeRecord;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class OvertimeQueueTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    public function test_org_wide_overtime_queue_lists_pending_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Queue Overtime Staff',
            'base_salary' => 12000,
            'pension_category' => 'covered',
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/employees/{$employeeId}/overtime-records", [
            'work_date' => '2026-06-10',
            'hours' => 2,
            'category' => 'working_day',
        ], $headers)->assertCreated();

        $response = $this->getJson('/api/v1/overtime-records?status=pending', $headers);

        $response->assertOk();
        $response->assertJsonPath('data.0.employee.full_name', 'Queue Overtime Staff');
        $response->assertJsonPath('data.0.status', 'pending');
    }

    public function test_overtime_record_can_be_approved_from_queue(): void
    {
        $this->seed(DatabaseSeeder::class);
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Approve Queue Staff',
            'base_salary' => 15000,
            'pension_category' => 'covered',
        ], $headers)->json('data.id');

        $record = OvertimeRecord::query()->create([
            'employee_id' => $employeeId,
            'work_date' => '2026-06-11',
            'hours' => 3,
            'category' => 'sunday',
            'status' => 'pending',
        ]);

        $this->postJson("/api/v1/overtime-records/{$record->id}/approve", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }
}
