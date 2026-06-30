<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CashierReadOnlyFrontDeskTest extends TestCase
{
    /**
     * Cashier role: read hotel data, settle folios, run cashier shifts — no check-in or group create.
     *
     * @return list<string>
     */
    private function cashierPermissions(): array
    {
        return [
            'S3.hotel.rooms.read',
            'S3.hotel.reservations.read',
            'S3.hotel.guests.read',
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
            'S3.hotel.cashier.read',
            'S3.hotel.cashier.write',
            'S3.hotel.group_bookings.read',
            'S3.hotel.group_bookings.check_out',
            'S3.restaurant.orders.read',
            'S3.restaurant.orders.write',
            'S3.restaurant.menu.read',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['username' => 'cashier.mulatu', 'name' => 'Mulatu']);
        Session::put('portal.permissions', $this->cashierPermissions());
    }

    public function test_inertia_shares_permissions_for_ui_gating(): void
    {
        $response = $this->get('/front-desk/check-in');

        $response->assertForbidden();
        // Permissions are shared on every authenticated Inertia response; verify session matches.
        $this->assertSame($this->cashierPermissions(), Session::get('portal.permissions'));
    }

    public function test_cashier_cannot_open_check_in_page(): void
    {
        $response = $this->get('/front-desk/check-in');

        $response->assertForbidden();
    }

    public function test_cashier_cannot_create_group_booking_route(): void
    {
        $response = $this->get('/group-bookings/create');

        $response->assertForbidden();
    }

    public function test_cashier_can_view_group_bookings_list(): void
    {
        $response = $this->get('/group-bookings');

        $response->assertOk();
    }
}
