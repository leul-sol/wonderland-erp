<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class FrontDeskPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['id' => 5, 'username' => 'receptionist', 'name' => 'Reception']);
        Session::put('portal.permissions', [
            'S3.hotel.rooms.read',
            'S3.hotel.reservations.read',
            'S3.hotel.reservations.write',
            'S3.hotel.checkinout.write',
            'S3.hotel.guests.read',
            'S3.hotel.guests.write',
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
        ]);
    }

    public function test_rooms_page_renders_with_room_data(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rooms')->once()->with(null)->andReturn([
                'data' => [[
                    'id' => 1,
                    'room_number' => '101',
                    'floor' => 1,
                    'status' => 'available',
                    'room_type' => ['id' => 1, 'name' => 'Standard'],
                ]],
            ]);
        });

        $response = $this->get('/front-desk/rooms');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FrontDesk/Rooms/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('rooms', 1));
    }

    public function test_check_in_route_opens_modal_on_rooms_page(): void
    {
        $response = $this->get('/front-desk/check-in');

        $response->assertRedirect(route('front-desk.rooms.index', ['open' => 'check-in']));
    }

    public function test_check_in_from_guests_redirects_to_guests_index(): void
    {
        $response = $this->get('/front-desk/check-in?from=guests&guest_id=5');

        $response->assertRedirect(route('front-desk.guests.index', [
            'open' => 'check-in',
            'guest_id' => 5,
        ]));
    }

    public function test_reservations_index_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                'reservations' => [
                    'data' => [[
                        'id' => 1,
                        'confirmation_code' => 'WH-ABC123',
                        'guest_name' => 'Jane Guest',
                        'status' => 'confirmed',
                        'check_in_date' => '2026-06-24',
                        'check_out_date' => '2026-06-25',
                    ]],
                ],
                'roomTypes' => ['data' => []],
                'guests' => ['data' => ['data' => []]],
            ]);
        });

        $response = $this->get('/front-desk/reservations');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FrontDesk/Reservations/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('pageLoad.reservations', 1));
    }

    public function test_reservation_show_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('reservation')->once()->with(7)->andReturn([
                'data' => [
                    'id' => 7,
                    'confirmation_code' => 'WH-XYZ',
                    'guest_name' => 'Jane Guest',
                    'status' => 'confirmed',
                    'room_type_id' => 1,
                    'check_in_date' => '2026-06-24',
                    'check_out_date' => '2026-06-25',
                ],
            ]);
            $mock->shouldReceive('rooms')->once()->with('available')->andReturn([
                'data' => [[
                    'id' => 10,
                    'room_number' => '101',
                    'floor' => 1,
                    'status' => 'available',
                    'room_type' => ['id' => 1, 'name' => 'Standard'],
                ]],
            ]);
        });

        $this->get('/front-desk/reservations/7')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('FrontDesk/Reservations/Show')->where('reservation.id', 7));
    }

    public function test_guests_index_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('guestProfiles')->once()->andReturn([
                'data' => ['data' => [['id' => 2, 'full_name' => 'Helen Assefa']]],
            ]);
        });

        $response = $this->get('/front-desk/guests');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('FrontDesk/Guests/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('guests', 1));
    }

    public function test_guest_edit_redirects_to_index_modal(): void
    {
        $response = $this->get('/front-desk/guests/2/edit');

        $response->assertRedirect(route('front-desk.guests.index', ['open' => 'edit', 'id' => 2]));
    }

    public function test_create_guest_stays_on_index(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createGuestProfile')
                ->once()
                ->andReturn(['data' => ['id' => 9, 'full_name' => 'Test Guest']]);
        });

        $response = $this->post('/front-desk/guests', [
            'full_name' => 'Test Guest',
        ]);

        $response->assertRedirect(route('front-desk.guests.index'));
        $response->assertSessionHas('success');
    }

    public function test_folio_invoice_download_returns_json(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('folioInvoice')->once()->with(5)->andReturn([
                'data' => [
                    'folio_number' => 'FOL-00001',
                    'guest_full_name' => 'Jane Guest',
                    'total_charges' => '3000.00',
                ],
            ]);
        });

        $response = $this->get('/front-desk/folios/5/invoice');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json');
        $this->assertStringContainsString('FOL-00001', $response->streamedContent());
    }

    public function test_reservation_cancel_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('cancelReservation')->once()->with(4)->andReturn(['data' => ['id' => 4, 'status' => 'cancelled']]);
        });

        $this->put('/front-desk/reservations/4/cancel')->assertRedirect();
    }

    public function test_folio_show_renders_one_screen_payload(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('folio')->once()->with(5)->andReturn([
                'data' => [
                    'id' => 5,
                    'reservation_id' => 9,
                    'status' => 'open',
                    'total_charges' => '3000.00',
                    'total_payments' => '0.00',
                    'balance' => '3000.00',
                    'lines' => [[
                        'id' => 1,
                        'description' => 'Room rent',
                        'line_type' => 'charge',
                        'charge_category' => 'room',
                        'subtotal' => '2500.00',
                        'service_charge_amount' => '250.00',
                        'vat_amount' => '250.00',
                        'amount' => '3000.00',
                    ]],
                ],
            ]);
            $mock->shouldReceive('reservation')->once()->with(9)->andReturn([
                'data' => [
                    'id' => 9,
                    'guest_name' => 'Jane Guest',
                    'status' => 'checked_in',
                    'confirmation_code' => 'WH-ABC',
                    'check_in_date' => '2026-06-24',
                    'check_out_date' => '2026-06-25',
                    'room' => ['room_number' => '101'],
                ],
            ]);
            $mock->shouldReceive('cashierShifts')->once()->andReturn(['data' => ['data' => []]]);
        });

        $response = $this->get('/front-desk/folios/5');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('FrontDesk/Folios/Show')
            ->where('folio.id', 5)
            ->where('reservation.guest_name', 'Jane Guest')
            ->where('canCheckout', true)
        );
    }

    public function test_folio_show_hides_checkout_for_cashier(): void
    {
        Session::put('portal.user', ['id' => 6, 'username' => 'cashier.mulatu', 'name' => 'Mulatu', 'roles' => [['name' => 'cashier']]]);
        Session::put('portal.permissions', [
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
            'S3.hotel.reservations.read',
        ]);

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('folio')->once()->with(6)->andReturn([
                'data' => [
                    'id' => 6,
                    'status' => 'settled',
                    'balance' => '0.00',
                    'total_charges' => '3000.00',
                    'total_payments' => '3000.00',
                    'lines' => [],
                    'reservation_id' => 10,
                ],
            ]);
            $mock->shouldReceive('reservation')->once()->with(10)->andReturn([
                'data' => [
                    'id' => 10,
                    'guest_name' => 'Jane Guest',
                    'status' => 'checked_in',
                ],
            ]);
            $mock->shouldReceive('cashierShifts')->once()->andReturn(['data' => ['data' => []]]);
        });

        $response = $this->get('/front-desk/folios/6');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('FrontDesk/Folios/Show')
            ->where('canSettle', true)
            ->where('canCheckout', false)
        );
    }

    public function test_guest_cannot_access_front_desk(): void
    {
        Session::flush();

        $this->get('/front-desk/rooms')->assertRedirect('/login');
    }

    public function test_user_without_permission_gets_forbidden(): void
    {
        Session::put('portal.permissions', ['S4.bi.dashboards.read']);

        $this->get('/front-desk/rooms')->assertForbidden();
    }
}
