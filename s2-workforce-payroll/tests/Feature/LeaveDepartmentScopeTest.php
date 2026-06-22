<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class LeaveDepartmentScopeTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_department_head_only_sees_own_department_leave_requests(): void
    {
        $foDeptId = Department::query()->where('code', 'FO')->value('id');
        $fbDeptId = Department::query()->where('code', 'FB')->value('id');

        $this->assertNotNull($foDeptId);
        $this->assertNotNull($fbDeptId);

        $foEmployee = Employee::query()->create([
            'employee_number' => 'EMP-90001',
            'full_name' => 'FO Staff',
            'base_salary' => 10000,
            'department_id' => $foDeptId,
            'status' => 'active',
        ]);

        $fbEmployee = Employee::query()->create([
            'employee_number' => 'EMP-90002',
            'full_name' => 'FB Staff',
            'base_salary' => 10000,
            'department_id' => $fbDeptId,
            'status' => 'active',
        ]);

        LeaveRequest::query()->create([
            'request_number' => 'LR-90001',
            'employee_id' => $foEmployee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-02',
            'days_requested' => 2,
            'status' => 'pending',
        ]);

        LeaveRequest::query()->create([
            'request_number' => 'LR-90002',
            'employee_id' => $fbEmployee->id,
            'leave_type' => 'sick',
            'start_date' => '2026-07-03',
            'end_date' => '2026-07-04',
            'days_requested' => 2,
            'status' => 'pending',
        ]);

        $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($foDeptId) {
            $mock->shouldReceive('verify')->andReturn([
                'valid' => true,
                'user' => [
                    'sub' => 99,
                    'permissions' => [
                        'S2.workforce.leave_requests.read',
                        'S2.workforce.leave_requests.approve',
                    ],
                    'roles' => ['department_head'],
                    'dept_scope' => (string) $foDeptId,
                ],
            ]);
        });

        $headers = ['Authorization' => 'Bearer dept-head-token'];

        $this->getJson('/api/v1/leave-requests', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee.department.id', $foDeptId);
    }

    public function test_department_head_cannot_approve_other_department_leave(): void
    {
        $foDeptId = Department::query()->where('code', 'FO')->value('id');
        $fbDeptId = Department::query()->where('code', 'FB')->value('id');

        $fbEmployee = Employee::query()->create([
            'employee_number' => 'EMP-90003',
            'full_name' => 'FB Staff Two',
            'base_salary' => 10000,
            'department_id' => $fbDeptId,
            'status' => 'active',
        ]);

        $leave = LeaveRequest::query()->create([
            'request_number' => 'LR-90003',
            'employee_id' => $fbEmployee->id,
            'leave_type' => 'annual',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'days_requested' => 2,
            'status' => 'pending',
        ]);

        $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($foDeptId) {
            $mock->shouldReceive('verify')->andReturn([
                'valid' => true,
                'user' => [
                    'sub' => 99,
                    'permissions' => [
                        'S2.workforce.leave_requests.read',
                        'S2.workforce.leave_requests.approve',
                    ],
                    'roles' => ['department_head'],
                    'dept_scope' => (string) $foDeptId,
                ],
            ]);
        });

        $this->postJson("/api/v1/leave-requests/{$leave->id}/approve", [], [
            'Authorization' => 'Bearer dept-head-token',
        ])->assertForbidden();
    }
}
