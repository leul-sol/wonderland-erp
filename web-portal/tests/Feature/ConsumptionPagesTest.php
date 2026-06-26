<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class ConsumptionPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S3.restaurant.consumption.read',
            'S3.restaurant.consumption.write',
            'S3.restaurant.orders.write',
            'S2.workforce.employees.read',
        ]);
    }

    public function test_periods_page_renders_with_employee_picker(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('consumptionPeriods')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'employee_id' => 5,
                    'period_start' => '2026-06-01',
                    'period_end' => '2026-06-30',
                    'total_amount' => '487.03',
                    'status' => 'open',
                    'deduction_status' => 'accruing',
                ]],
            ]);
        });

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('employees')->once()->with('active')->andReturn([
                'data' => [[
                    'id' => 5,
                    'employee_number' => 'EMP-005',
                    'full_name' => 'Kitchen Staff',
                ]],
            ]);
        });

        $response = $this->get('/consumption/periods');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Consumption/Periods/Index')
            ->where('canWrite', true)
            ->has('periods', 1)
            ->has('employees', 1)
            ->where('employeeMap.5', 'Kitchen Staff')
        );
    }

    public function test_period_close_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('closeConsumptionPeriod')->once()->with(1)->andReturn([
                'data' => [
                    'id' => 1,
                    'status' => 'closed',
                    'deduction_status' => 'posted_to_payroll',
                ],
            ]);
        });

        $response = $this->post('/consumption/periods/1/close');

        $response->assertRedirect(route('consumption.periods.index'));
    }

    public function test_meal_order_show_renders_employee_name(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('order')->once()->with(4)->andReturn([
                'data' => [
                    'id' => 4,
                    'order_number' => 'ORD-0004',
                    'employee_consumption_period_id' => 1,
                    'folio_id' => null,
                    'status' => 'open',
                    'subtotal' => '385.00',
                    'service_charge_amount' => '38.50',
                    'vat_amount' => '63.53',
                    'total_amount' => '487.03',
                    'lines' => [],
                ],
            ]);
            $mock->shouldReceive('menuItems')->once()->andReturn(['data' => []]);
            $mock->shouldReceive('consumptionPeriods')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'employee_id' => 5,
                    'period_start' => '2026-06-01',
                    'period_end' => '2026-06-30',
                    'status' => 'open',
                    'total_amount' => '0.00',
                ]],
            ]);
        });

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('employee')->once()->with(5)->andReturn([
                'data' => [
                    'id' => 5,
                    'full_name' => 'Kitchen Staff',
                    'employee_number' => 'EMP-005',
                ],
            ]);
        });

        $response = $this->get('/consumption/orders/4');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Consumption/MealOrders/Show')
            ->where('order.id', 4)
            ->where('employee.full_name', 'Kitchen Staff')
        );
    }
}
