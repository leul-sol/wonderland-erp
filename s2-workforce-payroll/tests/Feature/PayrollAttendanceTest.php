<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class PayrollAttendanceTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_payroll_run_rejected_when_weekday_attendance_missing(): void
    {
        $headers = $this->authHeaders();

        $this->postJson('/api/v1/employees', [
            'full_name' => 'No Attendance Staff',
            'base_salary' => 12000,
        ], $headers)->assertCreated();

        $this->postJson('/api/v1/payroll-runs', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-05',
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
