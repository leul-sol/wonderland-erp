<?php

namespace Tests\Feature;

use App\Services\Api\S1IdentityClient;
use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrPayrollPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.workforce.employees.read',
            'S2.workforce.employees.create',
            'S2.workforce.leave_requests.read',
            'S2.workforce.leave_requests.create',
            'S2.workforce.leave_requests.approve',
            'S2.workforce.attendance.read',
            'S2.workforce.attendance.create',
            'S2.workforce.payroll_runs.read',
            'S2.workforce.payroll_runs.create',
            'S2.workforce.payroll_runs.approve',
            'S2.workforce.severance.read',
            'S2.workforce.severance.calculate',
            'S2.workforce.severance.pay',
            'S1.identity.users.read',
        ]);
    }

    public function test_employees_index_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('employees')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'employee_number' => 'EMP-0001',
                    'full_name' => 'Selam Tadesse',
                    'department' => ['name' => 'Front office'],
                    'base_salary' => '12000.00',
                    'status' => 'active',
                ]],
            ]);
        });

        $response = $this->get('/hr/employees');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Hr/Employees/Index')->has('employees', 1));
    }

    public function test_employee_show_includes_platform_user(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('employee')->once()->with(2)->andReturn([
                'data' => [
                    'id' => 2,
                    'employee_number' => 'EMP-0002',
                    'full_name' => 'Bereket Alemu',
                    'status' => 'active',
                    'base_salary' => '24000.00',
                ],
            ]);
        });

        $this->mock(S1IdentityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('usersByEmployeeId')->once()->with(2)->andReturn([
                'data' => [[
                    'id' => 9,
                    'username' => 'bereket.alemu',
                    'email' => 'bereket.alemu@wonderlandhotel.local',
                    'display_name' => 'Bereket Alemu',
                ]],
            ]);
        });

        $response = $this->get('/hr/employees/2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Employees/Show')
            ->where('platformUser.username', 'bereket.alemu')
        );
    }

    public function test_leave_requests_page_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('leaveRequests')->once()->andReturn(['data' => []]);
            $mock->shouldReceive('employees')->once()->with('active')->andReturn(['data' => []]);
        });

        $response = $this->get('/hr/leave-requests');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Hr/Leave/Index'));
    }

    public function test_payroll_run_show_includes_approval_flags(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('payrollRun')->once()->with(5)->andReturn([
                'data' => [
                    'id' => 5,
                    'run_number' => 'PR-2026-01',
                    'period_start' => '2026-06-01',
                    'period_end' => '2026-06-30',
                    'status' => 'draft',
                    'total_gross' => '50000.00',
                    'total_net' => '42000.00',
                    'lines' => [],
                ],
            ]);
        });

        $response = $this->get('/payroll/runs/5');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Payroll/Runs/Show')
            ->where('canSubmit', true)
            ->where('canApprove', false)
        );
    }

    public function test_severance_page_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('severanceCalculations')->once()->andReturn(['data' => []]);
            $mock->shouldReceive('employees')->once()->with('active')->andReturn(['data' => []]);
        });

        $response = $this->get('/payroll/severance');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Payroll/Severance/Index'));
    }
}
