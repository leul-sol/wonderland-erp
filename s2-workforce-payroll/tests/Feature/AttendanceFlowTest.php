<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_attendance_record_create_and_list(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Nahom Tesfaye',
            'base_salary' => 11000,
        ], $headers)->json('data.id');

        $created = $this->postJson('/api/v1/attendance-records', [
            'employee_id' => $employeeId,
            'work_date' => '2026-06-15',
            'check_in' => '08:00',
            'check_out' => '17:00',
            'status' => 'present',
        ], $headers);

        $created->assertCreated()
            ->assertJsonPath('data.hours_worked', '9.00');

        $this->getJson('/api/v1/attendance-records?employee_id='.$employeeId, $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
