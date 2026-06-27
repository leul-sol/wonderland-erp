<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class HrOffboardingPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S2.workforce.offboarding.read',
            'S2.workforce.offboarding.create',
            'S2.workforce.offboarding.update',
            'S2.workforce.severance.read',
            'S2.hr.assets.write',
        ]);
    }

    public function test_offboarding_index_renders(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('offboardingRecords')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'employee_id' => 3,
                    'employee_name' => 'Hana Bekele',
                    'reason' => 'resignation',
                    'last_working_day' => '2026-06-30',
                    'clearance_status' => 'pending',
                    'severance_amount' => '12000.00',
                ]],
            ]);
            $mock->shouldReceive('employees')->once()->with('active')->andReturn([
                'data' => [
                    ['id' => 4, 'full_name' => 'Dawit Haile'],
                ],
            ]);
        });

        $response = $this->get('/hr/offboarding');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Offboarding/Index')
            ->where('canCreate', true));
        $this->assertDeferredInertia($response, fn ($page) => $page
            ->has('pageLoad.offboardingRecords', 1)
            ->has('pageLoad.eligibleEmployees', 1)
        );
    }

    public function test_offboarding_store_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createOffboarding')->once()->with(5, [
                'reason' => 'termination',
                'last_working_day' => '2026-06-28',
                'notes' => 'Policy breach',
                'calculate_severance' => true,
            ])->andReturn(['data' => ['id' => 8, 'employee_id' => 5]]);
        });

        $response = $this->post('/hr/offboarding', [
            'employee_id' => 5,
            'reason' => 'termination',
            'last_working_day' => '2026-06-28',
            'notes' => 'Policy breach',
            'calculate_severance' => true,
        ]);

        $response->assertRedirect(route('hr.offboarding.show', 8));
        $response->assertSessionHas('success');
    }

    public function test_offboarding_show_renders_dead_file_wizard(): void
    {
        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('offboardingRecord')->once()->with(2)->andReturn([
                'data' => [
                    'id' => 2,
                    'employee_id' => 6,
                    'employee_name' => 'Selam Tadesse',
                    'employee' => [
                        'id' => 6,
                        'employee_number' => 'EMP-0006',
                        'full_name' => 'Selam Tadesse',
                        'status' => 'active',
                    ],
                    'reason' => 'resignation',
                    'initiated_date' => '2026-06-01',
                    'last_working_day' => '2026-06-30',
                    'clearance_status' => 'in_progress',
                    'severance_amount' => '8000.00',
                    'notes' => 'Handover complete',
                ],
            ]);
            $mock->shouldReceive('employeeAssets')->once()->with(6)->andReturn([
                'data' => [
                    [
                        'id' => 10,
                        'asset_type' => ['name' => 'Laptop'],
                        'serial_number' => 'LAP-99',
                        'assigned_date' => '2026-01-01',
                        'returned_date' => null,
                    ],
                ],
            ]);
        });

        $response = $this->get('/hr/offboarding/2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Hr/Offboarding/Show')
            ->has('outstandingAssets', 1)
            ->where('canUpdate', true));
    }

    public function test_offboarding_update_posts_to_s2(): void
    {
        $this->withoutMiddleware();

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateOffboarding')->once()->with(3, [
                'clearance_status' => 'in_progress',
            ])->andReturn(['data' => ['id' => 3]]);
        });

        $response = $this->patch('/hr/offboarding/3', [
            'clearance_status' => 'in_progress',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
