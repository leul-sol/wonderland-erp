<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS2Auth;
use Tests\TestCase;

class DepartmentScopeTest extends TestCase
{
    use MocksS2Auth;
    use RefreshDatabase;

    private int $foDeptId;

    private int $fbDeptId;

    private Employee $foEmployee;

    private Employee $fbEmployee;

    private array $deptHeadHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->foDeptId = (int) Department::query()->where('code', 'FO')->value('id');
        $this->fbDeptId = (int) Department::query()->where('code', 'FB')->value('id');

        $this->foEmployee = Employee::query()->create([
            'employee_number' => 'EMP-80001',
            'full_name' => 'FO Scoped',
            'base_salary' => 10000,
            'department_id' => $this->foDeptId,
            'status' => 'active',
        ]);

        $this->fbEmployee = Employee::query()->create([
            'employee_number' => 'EMP-80002',
            'full_name' => 'FB Scoped',
            'base_salary' => 10000,
            'department_id' => $this->fbDeptId,
            'status' => 'active',
        ]);

        $this->mockDepartmentHead($this->foDeptId);
        $this->deptHeadHeaders = ['Authorization' => 'Bearer dept-head-token'];
    }

    public function test_department_head_employee_list_is_scoped(): void
    {
        $this->getJson('/api/v1/employees', $this->deptHeadHeaders)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->foEmployee->id);
    }

    public function test_department_head_cannot_view_other_department_employee(): void
    {
        $this->getJson('/api/v1/employees/'.$this->fbEmployee->id, $this->deptHeadHeaders)
            ->assertForbidden();
    }

    public function test_department_head_attendance_list_is_scoped(): void
    {
        AttendanceRecord::query()->create([
            'employee_id' => $this->foEmployee->id,
            'work_date' => '2026-06-10',
            'status' => 'present',
            'hours_worked' => 8,
        ]);

        AttendanceRecord::query()->create([
            'employee_id' => $this->fbEmployee->id,
            'work_date' => '2026-06-10',
            'status' => 'present',
            'hours_worked' => 8,
        ]);

        $this->getJson('/api/v1/attendance-records', $this->deptHeadHeaders)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.employee_id', $this->foEmployee->id);
    }

    public function test_department_head_cannot_view_other_department_assets(): void
    {
        $assetTypeId = AssetType::query()->firstOrFail()->id;

        $this->postJson('/api/v1/employees/'.$this->fbEmployee->id.'/assets', [
            'asset_type_id' => $assetTypeId,
        ], $this->deptHeadHeaders)->assertForbidden();

        $this->getJson('/api/v1/employees/'.$this->fbEmployee->id.'/assets', $this->deptHeadHeaders)
            ->assertForbidden();
    }

    public function test_department_head_cannot_view_other_department_disciplinary_records(): void
    {
        $this->getJson('/api/v1/employees/'.$this->fbEmployee->id.'/disciplinary-records', $this->deptHeadHeaders)
            ->assertForbidden();
    }

    public function test_department_head_cannot_view_other_department_leave_balances(): void
    {
        $this->getJson('/api/v1/employees/'.$this->fbEmployee->id.'/leave-balances', $this->deptHeadHeaders)
            ->assertForbidden();
    }

    private function mockDepartmentHead(int $departmentId): void
    {
        $this->mock(\App\Services\S1AuthService::class, function ($mock) use ($departmentId) {
            $mock->shouldReceive('verify')->andReturn([
                'valid' => true,
                'user' => [
                    'sub' => 99,
                    'permissions' => [
                        'S2.workforce.employees.read',
                        'S2.hr.disciplinary.read',
                        'S2.hr.assets.read',
                        'S2.workforce.leave_balances.read',
                        'S2.workforce.attendance.read',
                    ],
                    'roles' => ['department_head'],
                    'dept_scope' => (string) $departmentId,
                ],
            ]);
        });
    }
}
