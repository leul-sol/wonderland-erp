<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS2Auth;
use Tests\Concerns\SeedsPayrollAttendance;
use Tests\TestCase;

class PayrollDeductionTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;
    use SeedsPayrollAttendance;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 55, 'entry_number' => 'JE-00055']], 201),
        ]);
    }

    public function test_payroll_run_applies_staff_meal_deductions_to_net_pay(): void
    {
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Tigist Worku',
            'base_salary' => 20000,
        ], $headers)->json('data.id');

        $this->seedWeekdayAttendance($employeeId, '2026-06-01', '2026-06-30');

        EmployeeDeduction::query()->create([
            'employee_id' => $employeeId,
            'deduction_type' => 'staff_meal',
            'amount' => 500,
            'source_reference' => 'CONSUMPTION-1',
            'idempotency_key' => 'meal-payroll-1',
            'status' => 'applied',
        ]);

        $run = $this->postJson('/api/v1/payroll-runs', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ], $headers)->assertCreated();

        $line = collect($run->json('data.lines'))->firstWhere('employee_id', $employeeId);

        $this->assertSame('500.00', $line['other_deductions']);

        $runId = $run->json('data.id');

        $this->postJson("/api/v1/payroll-runs/{$runId}/submit", [], $headers)->assertOk();

        $this->postJson("/api/v1/payroll-runs/{$runId}/approve", [], array_merge($headers, [
            'Idempotency-Key' => 'payroll-deduction-'.$runId,
        ]))->assertOk();

        $this->assertDatabaseHas('employee_deductions', [
            'employee_id' => $employeeId,
            'payroll_run_id' => $runId,
        ]);
    }
}
