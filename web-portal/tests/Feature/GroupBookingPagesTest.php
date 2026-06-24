<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class GroupBookingPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S3.hotel.group_bookings.read',
            'S3.hotel.group_bookings.create',
            'S3.hotel.group_bookings.check_in',
            'S3.hotel.group_bookings.check_out',
            'S3.hotel.folios.write',
        ]);
    }

    public function test_group_booking_index_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('groupBookings')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'group_code' => 'GRP-ABC123',
                    'group_name' => 'Corporate Retreat',
                    'contact_name' => 'Planner',
                    'room_count' => 2,
                    'status' => 'confirmed',
                ]],
            ]);
        });

        $response = $this->get('/group-bookings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('GroupBookings/Index')->has('groupBookings', 1));
    }

    public function test_group_booking_create_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('roomTypes')->once()->andReturn([
                'data' => [['id' => 1, 'name' => 'Standard']],
            ]);
        });

        $response = $this->get('/group-bookings/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('GroupBookings/Create'));
    }

    public function test_group_booking_show_includes_folios_for_checkout(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('groupBooking')->once()->with(3)->andReturn([
                'data' => [
                    'id' => 3,
                    'group_code' => 'GRP-TEST01',
                    'group_name' => 'Wedding Party',
                    'contact_name' => 'Coordinator',
                    'room_count' => 2,
                    'status' => 'checked_in',
                    'reservations' => [
                        [
                            'id' => 10,
                            'guest_name' => 'Guest A',
                            'status' => 'checked_in',
                            'folio_id' => 20,
                            'room_type_id' => 1,
                            'room_type' => ['name' => 'Standard'],
                        ],
                    ],
                ],
            ]);
            $mock->shouldReceive('rooms')->once()->with('available')->andReturn(['data' => []]);
            $mock->shouldReceive('folio')->once()->with(20)->andReturn([
                'data' => [
                    'id' => 20,
                    'status' => 'open',
                    'balance' => '3000.00',
                ],
            ]);
        });

        $response = $this->get('/group-bookings/3');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('GroupBookings/Show')
            ->where('groupBooking.id', 3)
            ->has('folios.20')
        );
    }
}
