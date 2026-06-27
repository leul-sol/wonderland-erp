<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class CashierSettingsPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S3.hotel.cashier.read',
            'S3.hotel.cashier.write',
            'S3.hotel.rooms.read',
            'S3.hotel.rooms.write',
        ]);
    }

    public function test_cashier_shifts_index_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cashierShifts')->once()->andReturn([
                'data' => [
                    'data' => [[
                        'id' => 2,
                        'status' => 'open',
                        'opened_at' => '2026-06-21T08:00:00Z',
                        'opening_cash_float' => '500.00',
                    ]],
                ],
            ]);
        });

        $response = $this->get('/front-desk/cashier-shifts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FrontDesk/CashierShifts/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->where('pageLoad.openShift.id', 2));
    }

    public function test_cashier_shift_show_includes_report(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cashierShift')->once()->with(2)->andReturn([
                'data' => [
                    'id' => 2,
                    'status' => 'open',
                    'opening_cash_float' => '500.00',
                ],
            ]);
            $mock->shouldReceive('cashierShiftReport')->once()->with(2)->andReturn([
                'data' => [
                    'expected_cash' => '1250.00',
                    'shift' => ['id' => 2],
                ],
            ]);
        });

        $response = $this->get('/front-desk/cashier-shifts/2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('FrontDesk/CashierShifts/Show')
            ->where('report.expected_cash', '1250.00')
        );
    }

    public function test_open_cashier_shift_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('openCashierShift')
                ->once()
                ->with(['opening_cash_float' => 500.0])
                ->andReturn(['data' => ['id' => 3, 'status' => 'open']]);
        });

        $response = $this->post('/front-desk/cashier-shifts', [
            'opening_cash_float' => '500',
        ]);

        $response->assertRedirect(route('front-desk.cashier-shifts.show', 3));
    }

    public function test_close_cashier_shift_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('closeCashierShift')
                ->once()
                ->with(2, ['closing_cash_counted' => 1250.0])
                ->andReturn(['data' => ['id' => 2, 'status' => 'closed']]);
        });

        $response = $this->post('/front-desk/cashier-shifts/2/close', [
            'closing_cash_counted' => '1250',
        ]);

        $response->assertRedirect(route('front-desk.cashier-shifts.show', 2));
    }

    public function test_hotel_settings_room_types_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                'roomTypes' => [
                    'data' => [[
                        'id' => 1,
                        'code' => 'STD',
                        'name' => 'Standard',
                        'base_rate' => '2500.00',
                        'max_occupancy' => 2,
                        'is_active' => true,
                    ]],
                ],
                'rooms' => ['data' => []],
            ]);
        });

        $response = $this->get('/front-desk/settings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FrontDesk/Settings/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('pageLoad.roomTypes', 1));
    }
}
