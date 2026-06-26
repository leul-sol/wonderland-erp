<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrOvertimePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.workforce.overtime.read',
            'S2.workforce.overtime.create',
            'S2.workforce.overtime.approve',
        ]);
    }

    public function test_overtime_index_renders_pending_queue(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('overtimeRecords')->once()->with(['status' => 'pending'])->andReturn([
                'data' => [[
                    'id' => 1,
                    'employee_id' => 4,
                    'employee' => [
                        'id' => 4,
                        'full_name' => 'Dawit Haile',
                        'department' => ['id' => 1, 'name' => 'Front Office'],
                    ],
                    'work_date' => '2026-06-10',
                    'hours' => '2.00',
                    'category' => 'working_day',
                    'status' => 'pending',
                ]],
            ]);
            $mock->shouldReceive('employees')->once()->with('active')->andReturn(['data' => []]);
            $mock->shouldReceive('overtimeRates')->once()->andReturn([
                'data' => [['id' => 1, 'category' => 'working_day', 'multiplier' => '1.50']],
            ]);
        });

        $response = $this->get('/hr/overtime');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Overtime/Index')
            ->where('filterStatus', 'pending')
            ->has('overtimeRecords', 1)
            ->where('canApprove', true));
    }

    public function test_overtime_store_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createOvertimeRecord')->once()->with(5, [
                'work_date' => '2026-06-12',
                'hours' => 2.5,
                'category' => 'night',
            ])->andReturn(['data' => ['id' => 9]]);
        });

        $response = $this->post('/hr/overtime', [
            'employee_id' => 5,
            'work_date' => '2026-06-12',
            'hours' => 2.5,
            'category' => 'night',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_overtime_approve_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('approveOvertimeRecord')->once()->with(3)->andReturn([
                'data' => ['id' => 3, 'status' => 'approved'],
            ]);
        });

        $response = $this->post('/hr/overtime/3/approve');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
