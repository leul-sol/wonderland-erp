<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class S3BackOfficePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S3.hotel.reservations.read',
            'S3.hotel.reservations.write',
            'S3.hotel.rooms.read',
            'S3.hotel.rooms.write',
            'S3.hotel.guests.read',
            'S3.restaurant.orders.read',
            'S3.restaurant.orders.write',
        ]);
    }

    public function test_reservation_create_redirects_to_index(): void
    {
        $response = $this->get('/front-desk/reservations/create');

        $response->assertRedirect(route('front-desk.reservations.index', ['open' => 'create']));
    }

    public function test_store_reservation_without_check_in(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createReservation')
                ->once()
                ->andReturn(['data' => ['id' => 12, 'status' => 'confirmed']]);
        });

        $response = $this->post('/front-desk/reservations', [
            'guest_name' => 'Future Guest',
            'room_type_id' => 1,
            'check_in_date' => '2026-07-01',
            'check_out_date' => '2026-07-03',
        ]);

        $response->assertRedirect(route('front-desk.reservations.show', 12));
    }

    public function test_physical_rooms_settings_redirects_to_hotel_settings(): void
    {
        $response = $this->get('/front-desk/settings/rooms');

        $response->assertRedirect(route('front-desk.settings.index'));
    }

    public function test_cancel_order_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cancelOrder')->once()->with(5)->andReturn(['data' => ['id' => 5]]);
        });

        $response = $this->put('/fb/orders/5/cancel');

        $response->assertRedirect(route('fb.orders.index'));
    }

    public function test_remove_order_line_deletes_via_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('removeOrderLine')->once()->with(5, 9)->andReturn(['data' => []]);
        });

        $response = $this->delete('/fb/orders/5/lines/9');

        $response->assertRedirect();
    }
}
