<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrOrganizationPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.hr.departments.read',
            'S2.hr.departments.write',
            'S2.workforce.positions.read',
            'S2.workforce.positions.create',
            'S2.workforce.positions.update',
            'S2.workforce.positions.delete',
        ]);
    }

    public function test_departments_index_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('departments')->once()->andReturn([
                'data' => [
                    ['id' => 1, 'code' => 'FO', 'name' => 'Front Office', 'head_employee_id' => null],
                ],
            ]);
            $mock->shouldReceive('employees')->once()->with('active')->andReturn([
                'data' => [['id' => 5, 'full_name' => 'Hana Bekele']],
            ]);
        });

        $response = $this->get('/hr/departments');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Organization/Departments/Index')
            ->has('departments', 1)
            ->where('canWrite', true));
    }

    public function test_department_store_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createDepartment')->once()->with([
                'code' => 'hk',
                'name' => 'Housekeeping',
                'head_employee_id' => null,
            ])->andReturn(['data' => ['id' => 9]]);
        });

        $response = $this->post('/hr/departments', [
            'code' => 'hk',
            'name' => 'Housekeeping',
        ]);

        $response->assertRedirect(route('hr.departments.index'));
        $response->assertSessionHas('success');
    }

    public function test_department_edit_page_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('department')->once()->with(2)->andReturn([
                'data' => ['id' => 2, 'code' => 'FN', 'name' => 'Finance', 'head_employee_id' => null],
            ]);
            $mock->shouldReceive('employees')->once()->with('active')->andReturn(['data' => []]);
        });

        $response = $this->get('/hr/departments/2/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Hr/Organization/Departments/Edit'));
    }

    public function test_positions_index_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('positions')->once()->andReturn([
                'data' => [
                    [
                        'id' => 1,
                        'title' => 'Receptionist',
                        'department_id' => 1,
                        'department' => ['id' => 1, 'name' => 'Front Office'],
                        'grade' => 'G3',
                        'transport_allowance' => '500.00',
                        'housing_allowance' => '0.00',
                    ],
                ],
            ]);
            $mock->shouldReceive('departments')->once()->andReturn([
                'data' => [['id' => 1, 'code' => 'FO', 'name' => 'Front Office']],
            ]);
        });

        $response = $this->get('/hr/positions');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Organization/Positions/Index')
            ->has('positions', 1)
            ->where('canCreate', true));
    }

    public function test_position_store_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createPosition')->once()->with([
                'title' => 'Chef',
                'department_id' => 3,
                'grade' => 'G5',
                'transport_allowance' => 750.0,
                'housing_allowance' => 0.0,
            ])->andReturn(['data' => ['id' => 12]]);
        });

        $response = $this->post('/hr/positions', [
            'title' => 'Chef',
            'department_id' => 3,
            'grade' => 'G5',
            'transport_allowance' => 750,
        ]);

        $response->assertRedirect(route('hr.positions.index'));
        $response->assertSessionHas('success');
    }

    public function test_position_edit_page_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('position')->once()->with(4)->andReturn([
                'data' => [
                    'id' => 4,
                    'title' => 'Accountant',
                    'department_id' => 2,
                    'grade' => 'G4',
                    'transport_allowance' => '600.00',
                    'housing_allowance' => '1000.00',
                ],
            ]);
            $mock->shouldReceive('departments')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/hr/positions/4/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Hr/Organization/Positions/Edit'));
    }
}
