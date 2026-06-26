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
            $mock->shouldReceive('groupBookings')->once()->with(null)->andReturn([
                'data' => [[
                    'id' => 1,
                    'group_code' => 'GRP-ABC123',
                    'group_name' => 'Corporate Retreat',
                    'contact_name' => 'Planner',
                    'room_count' => 2,
                    'status' => 'confirmed',
                ]],
            ]);
            $mock->shouldReceive('roomTypes')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/group-bookings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('GroupBookings/Index')
            ->has('groupBookings', 1)
            ->where('filters.tab', 'all')
        );
    }

    public function test_group_booking_index_filters_by_status(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('groupBookings')->once()->with('checked_in')->andReturn(['data' => []]);
            $mock->shouldReceive('roomTypes')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/group-bookings?tab=checked_in');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('filters.tab', 'checked_in'));
    }

    public function test_group_booking_create_redirects_to_index(): void
    {
        $response = $this->get('/group-bookings/create');

        $response->assertRedirect(route('group-bookings.index'));
    }

    public function test_group_booking_show_includes_lifecycle_and_folios(): void
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
            ->where('lifecycleCurrentStep', 'checked_in')
            ->where('allFoliosSettled', false)
            ->has('folios.20')
        );
    }

    public function test_group_check_in_posts_assignments(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkInGroupBooking')
                ->once()
                ->with(3, [[
                    'reservation_id' => 10,
                    'room_id' => 7,
                ]])
                ->andReturn(['data' => ['id' => 3, 'status' => 'checked_in']]);
        });

        $response = $this->post('/group-bookings/3/check-in', [
            'assignments' => [[
                'reservation_id' => 10,
                'room_id' => 7,
            ]],
        ]);

        $response->assertRedirect();
    }

    public function test_group_check_out_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('checkOutGroupBooking')
                ->once()
                ->with(3)
                ->andReturn(['data' => ['id' => 3, 'status' => 'checked_out']]);
        });

        $response = $this->post('/group-bookings/3/check-out');

        $response->assertRedirect(route('group-bookings.index'));
    }
}
