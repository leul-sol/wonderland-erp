<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrPortalNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.workforce.employees.read',
            'S2.workforce.employees.create',
            'S2.hr.departments.read',
            'S2.hr.departments.write',
            'S2.workforce.positions.read',
            'S2.workforce.positions.create',
            'S2.workforce.overtime.read',
            'S2.workforce.overtime.create',
            'S2.workforce.offboarding.read',
            'S2.workforce.offboarding.create',
            'S2.workforce.leave_requests.read',
            'S2.workforce.leave_requests.create',
            'S2.workforce.attendance.read',
            'S2.workforce.attendance.create',
            'S2.workforce.leave_types.read',
            'S2.workforce.overtime.update',
            'S2.hr.assets.read',
            'S2.hr.assets.write',
            'S2.workforce.payroll_runs.read',
            'S2.workforce.payroll_runs.create',
            'S2.workforce.severance.read',
        ]);
    }

    public function test_all_hr_and_payroll_index_pages_render(): void
    {
        $empty = ['data' => []];

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock) use ($empty): void {
            $mock->shouldReceive('employees')->andReturn($empty);
            $mock->shouldReceive('departments')->andReturn($empty);
            $mock->shouldReceive('positions')->andReturn($empty);
            $mock->shouldReceive('overtimeRecords')->andReturn($empty);
            $mock->shouldReceive('overtimeRates')->andReturn($empty);
            $mock->shouldReceive('offboardingRecords')->andReturn($empty);
            $mock->shouldReceive('leaveRequests')->andReturn($empty);
            $mock->shouldReceive('attendanceRecords')->andReturn($empty);
            $mock->shouldReceive('leaveTypes')->andReturn($empty);
            $mock->shouldReceive('assetTypes')->andReturn($empty);
            $mock->shouldReceive('payrollRuns')->andReturn($empty);
            $mock->shouldReceive('severanceCalculations')->andReturn($empty);
        });

        $pages = [
            ['/hr/employees', 'Hr/Employees/Index'],
            ['/hr/employees/create', 'Hr/Employees/Create'],
            ['/hr/departments', 'Hr/Organization/Departments/Index'],
            ['/hr/positions', 'Hr/Organization/Positions/Index'],
            ['/hr/overtime', 'Hr/Overtime/Index'],
            ['/hr/offboarding', 'Hr/Offboarding/Index'],
            ['/hr/leave-requests', 'Hr/Leave/Index'],
            ['/hr/attendance', 'Hr/Attendance/Index'],
            ['/hr/settings', 'Hr/Settings/Index'],
            ['/payroll/runs', 'Payroll/Runs/Index'],
            ['/payroll/runs/create', 'Payroll/Runs/Create'],
            ['/payroll/severance', 'Payroll/Severance/Index'],
        ];

        foreach ($pages as [$path, $component]) {
            $response = $this->get($path);
            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component($component));
        }
    }
}
