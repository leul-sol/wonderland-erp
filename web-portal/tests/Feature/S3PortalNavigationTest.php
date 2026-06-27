<?php

namespace Tests\Feature;

use App\Services\Api\S2WorkforceClient;
use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class S3PortalNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S3.hotel.rooms.read',
            'S3.hotel.rooms.write',
            'S3.hotel.reservations.read',
            'S3.hotel.reservations.write',
            'S3.hotel.reservations.write',
            'S3.hotel.checkinout.write',
            'S3.hotel.guests.read',
            'S3.hotel.guests.write',
            'S3.hotel.folios.read',
            'S3.hotel.folios.write',
            'S3.hotel.cashier.read',
            'S3.hotel.cashier.write',
            'S3.hotel.group_bookings.read',
            'S3.hotel.group_bookings.create',
            'S3.restaurant.menu.read',
            'S3.restaurant.menu.write',
            'S3.restaurant.orders.read',
            'S3.restaurant.orders.write',
            'S3.restaurant.consumption.read',
            'S3.restaurant.consumption.write',
            'S3.inventory.items.read',
            'S3.inventory.items.write',
            'S3.inventory.suppliers.write',
            'S3.inventory.reports.read',
            'S3.inventory.suppliers.read',
            'S3.inventory.purchase_orders.read',
            'S3.inventory.purchase_orders.write',
            'S2.workforce.employees.read',
        ]);
    }

    public function test_all_s3_hospitality_index_pages_render(): void
    {
        $empty = ['data' => []];
        $emptyPaginated = ['data' => ['data' => []]];

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock) use ($empty, $emptyPaginated): void {
            $mock->shouldReceive('fetchMany')->andReturnUsing(function (array $requests) use ($empty, $emptyPaginated): array {
                $pool = [
                    'reservations' => $empty,
                    'roomTypes' => $empty,
                    'guests' => ['data' => ['data' => []]],
                    'rooms' => $empty,
                    'orders' => $empty,
                    'folios' => $emptyPaginated,
                    'tables' => $empty,
                    'menuItems' => $empty,
                    'categories' => $empty,
                    'items' => $empty,
                    'purchaseOrders' => $empty,
                    'suppliers' => $empty,
                    'groupBookings' => $empty,
                ];
                $results = [];
                foreach (array_keys($requests) as $key) {
                    $results[$key] = $pool[$key] ?? $empty;
                }

                return $results;
            });
            $mock->shouldReceive('rooms')->andReturn($empty);
            $mock->shouldReceive('reservations')->andReturn($empty);
            $mock->shouldReceive('guestProfiles')->andReturn(['data' => ['data' => []]]);
            $mock->shouldReceive('roomTypes')->andReturn($empty);
            $mock->shouldReceive('folios')->andReturn($emptyPaginated);
            $mock->shouldReceive('cashierShifts')->andReturn(['data' => ['data' => []]]);
            $mock->shouldReceive('menuItems')->andReturn($empty);
            $mock->shouldReceive('orders')->andReturn($empty);
            $mock->shouldReceive('diningTables')->andReturn($empty);
            $mock->shouldReceive('menuCategories')->andReturn($empty);
            $mock->shouldReceive('menuCategories')->with(false)->andReturn($empty);
            $mock->shouldReceive('menuItemsCatalog')->andReturn($empty);
            $mock->shouldReceive('menuItemsCatalog')->with(false)->andReturn($empty);
            $mock->shouldReceive('inventoryItems')->andReturn($empty);
            $mock->shouldReceive('itemCategories')->andReturn($empty);
            $mock->shouldReceive('lowStockAlerts')->andReturn($empty);
            $mock->shouldReceive('expiryAlerts')->andReturn($empty);
            $mock->shouldReceive('stockValuation')->andReturn(['data' => ['total_value' => 0, 'lines' => []]]);
            $mock->shouldReceive('suppliers')->andReturn($empty);
            $mock->shouldReceive('purchaseOrders')->andReturn($empty);
            $mock->shouldReceive('consumptionPeriods')->andReturn($empty);
            $mock->shouldReceive('groupBookings')->andReturn($empty);
        });

        $this->mock(S2WorkforceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->andReturn([
                'employees' => ['data' => []],
                'departments' => ['data' => []],
                'positions' => ['data' => []],
            ]);
            $mock->shouldReceive('employees')->andReturn(['data' => []]);
        });

        $pages = [
            ['/front-desk/rooms', 'FrontDesk/Rooms/Index'],
            ['/front-desk/reservations', 'FrontDesk/Reservations/Index'],
            ['/front-desk/guests', 'FrontDesk/Guests/Index'],
            ['/front-desk/check-in', 'FrontDesk/CheckIn/Create'],
            ['/front-desk/folios', 'FrontDesk/Folios/Index'],
            ['/front-desk/cashier-shifts', 'FrontDesk/CashierShifts/Index'],
            ['/front-desk/settings', 'FrontDesk/Settings/Index'],
            ['/fb/menu', 'Fb/Menu/Index'],
            ['/fb/orders', 'Fb/Orders/Index'],
            ['/fb/settings', 'Fb/Settings/Index'],
            ['/fb/menu-categories', 'Fb/MenuCategories/Index'],
            ['/fb/menu-items', 'Fb/MenuItems/Index'],
            ['/fb/dining-tables', 'Fb/DiningTables/Index'],
            ['/inventory/items', 'Inventory/Items/Index'],
            ['/inventory/item-categories', 'Inventory/ItemCategories/Index'],
            ['/inventory/alerts', 'Inventory/Alerts/Index'],
            ['/inventory/valuation', 'Inventory/Valuation/Index'],
            ['/inventory/suppliers', 'Inventory/Suppliers/Index'],
            ['/inventory/purchase-orders', 'Inventory/PurchaseOrders/Index'],
            ['/consumption/periods', 'Consumption/Periods/Index'],
            ['/group-bookings', 'GroupBookings/Index'],
        ];

        foreach ($pages as [$path, $component]) {
            $response = $this->get($path);
            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component($component));
        }

        $redirects = [
            '/front-desk/reservations/create' => '/front-desk/reservations?open=create',
            '/front-desk/guests/create' => '/front-desk/guests?open=create',
            '/front-desk/settings/rooms' => '/front-desk/settings',
            '/fb/orders/create' => '/fb/orders?open=create',
            '/fb/menu-items/create' => '/fb/menu-items?open=create',
            '/inventory/items/create' => '/inventory/items?open=create',
            '/inventory/suppliers/create' => '/inventory/suppliers?open=create',
            '/inventory/purchase-orders/create' => '/inventory/purchase-orders?open=create',
            '/group-bookings/create' => '/group-bookings?open=create',
        ];

        foreach ($redirects as $path => $target) {
            $this->get($path)->assertRedirect($target);
        }
    }
}
