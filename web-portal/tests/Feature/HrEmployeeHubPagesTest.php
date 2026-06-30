<?php

namespace Tests\Feature;

use App\Services\Api\S1AdminClient;
use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrEmployeeHubPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.workforce.employees.read',
            'S2.workforce.employees.update',
            'S2.workforce.leave_balances.read',
            'S2.workforce.leave_requests.read',
            'S2.workforce.leave_requests.reject',
            'S2.hr.disciplinary.read',
            'S2.hr.disciplinary.write',
            'S2.hr.assets.read',
            'S2.hr.assets.write',
            'S2.hr.guarantors.read',
            'S2.hr.guarantors.write',
            'S2.workforce.loans.read',
            'S2.workforce.loans.create',
            'S2.payroll.payslips.read',
            'S2.workforce.payroll_runs.read',
            'S1.identity.users.read',
        ]);
    }

    public function test_employee_edit_redirects_to_show_modal(): void
    {
        $response = $this->get('/hr/employees/3/edit');

        $response->assertRedirect(route('hr.employees.show', ['employee' => 3, 'open' => 'edit']));
    }

    public function test_employee_show_hub_includes_tabs_data(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('employee')->once()->with(4)->andReturn([
                'data' => [
                    'id' => 4,
                    'employee_number' => 'EMP-0004',
                    'full_name' => 'Dawit Haile',
                    'status' => 'active',
                    'base_salary' => '22000.00',
                ],
            ]);
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                'leaveBalances' => ['data' => [['id' => 1, 'days_remaining' => '10.00']]],
                'employeeLeaveRequests' => ['data' => []],
            ]);
            $mock->shouldReceive('departments')->once()->andReturn(['data' => []]);
            $mock->shouldReceive('positions')->once()->andReturn(['data' => []]);
        });

        $this->mock(S1AdminClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('usersByEmployeeId')->once()->with(4)->andReturn(['data' => []]);
        });

        $response = $this->get('/hr/employees/4?tab=leave');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Employees/Show')
            ->where('activeTab', 'leave')
            ->has('leaveBalances', 1)
            ->where('canUpdate', true)
        );
    }

    public function test_leave_reject_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rejectLeaveRequest')->once()->with(12, 'Not enough coverage')->andReturn(['data' => []]);
        });

        $response = $this->post('/hr/leave-requests/12/reject', ['reason' => 'Not enough coverage']);

        $response->assertRedirect();
    }
}
