<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class FrontDeskNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['username' => 'receptionist', 'name' => 'Reception']);
        Session::put('portal.permissions', [
            'S3.hotel.rooms.read',
            'S3.hotel.rooms.write',
            'S3.hotel.reservations.read',
            'S3.hotel.reservations.write',
            'S3.hotel.checkinout.write',
            'S3.hotel.guests.read',
            'S3.hotel.guests.write',
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
            'S3.hotel.cashier.read',
            'S3.hotel.cashier.write',
            'S3.hotel.rooms.write',
        ]);
    }

    public function test_front_desk_index_pages_render(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rooms')->andReturn(['data' => []]);
            $mock->shouldReceive('reservations')->andReturn(['data' => []]);
            $mock->shouldReceive('guestProfiles')->andReturn(['data' => ['data' => []]]);
            $mock->shouldReceive('roomTypes')->andReturn(['data' => []]);
            $mock->shouldReceive('folios')->andReturn(['data' => ['data' => []]]);
            $mock->shouldReceive('cashierShifts')->andReturn(['data' => ['data' => []]]);
            $mock->shouldReceive('roomTypes')->andReturn(['data' => []]);
        });

        $pages = [
            ['/front-desk/rooms', 'FrontDesk/Rooms/Index'],
            ['/front-desk/reservations', 'FrontDesk/Reservations/Index'],
            ['/front-desk/guests', 'FrontDesk/Guests/Index'],
            ['/front-desk/guests/create', 'FrontDesk/Guests/Edit'],
            ['/front-desk/check-in', 'FrontDesk/CheckIn/Create'],
            ['/front-desk/folios', 'FrontDesk/Folios/Index'],
            ['/front-desk/cashier-shifts', 'FrontDesk/CashierShifts/Index'],
            ['/front-desk/settings', 'FrontDesk/Settings/Index'],
        ];

        foreach ($pages as [$path, $component]) {
            $response = $this->get($path);
            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component($component));
        }
    }
}
