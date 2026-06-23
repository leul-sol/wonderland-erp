<?php

namespace Tests\Feature;

use App\Services\Payroll\TaxCalculatorService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\Concerns\SeedsPayrollAttendance;
use Tests\TestCase;

class PayrollLifecycleTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;
    use SeedsPayrollAttendance;

    public function test_payroll_submit_approve_lock_lifecycle(): void
    {
        $this->seed(DatabaseSeeder::class);
        $headers = $this->authHeaders();

        $employeeId = $this->postJson('/api/v1/employees', [
            'full_name' => 'Lifecycle Staff',
            'base_salary' => 10000,
            'pension_category' => 'covered',
        ], $headers)->json('data.id');

        $this->seedWeekdayAttendance($employeeId, '2026-06-01', '2026-06-30');

        $runId = $this->postJson('/api/v1/payroll-runs', [
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ], $headers)->assertCreated()->json('data.id');

        $this->postJson("/api/v1/payroll-runs/{$runId}/submit", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_approval');

        $this->postJson("/api/v1/payroll-runs/{$runId}/approve", [], array_merge($headers, [
            'Idempotency-Key' => 'lifecycle-approve-'.$runId,
        ]))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->postJson("/api/v1/payroll-runs/{$runId}/lock", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'locked');
    }

    public function test_erca_tax_bracket_applies_to_payroll_line(): void
    {
        $tax = new TaxCalculatorService;

        $this->assertSame(0.0, $tax->calculate(600.0));
        $this->assertSame(165.0, $tax->calculate(1650.0));
        $this->assertSame(390.0, $tax->calculate(3200.0));
    }
}
