<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class FbPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['username' => 'cashier', 'name' => 'Cashier']);
        Session::put('portal.permissions', [
            'S3.restaurant.menu.read',
            'S3.restaurant.orders.read',
            'S3.restaurant.orders.write',
        ]);
    }

    public function test_menu_page_renders_items(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('menuItems')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'code' => 'BURGER-CL',
                    'name' => 'Classic Burger',
                    'price' => '450.00',
                    'category' => 'Mains',
                ]],
            ]);
        });

        $response = $this->get('/fb/menu');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/Menu/Index')
            ->has('menuItems', 1)
        );
    }

    public function test_order_create_page_lists_open_folios(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('folios')->once()->with('open')->andReturn([
                'data' => [
                    'data' => [[
                        'id' => 5,
                        'reservation_id' => 9,
                        'status' => 'open',
                        'balance' => '3000.00',
                    ]],
                ],
            ]);
        });

        $response = $this->get('/fb/orders/create?folio_id=5');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/Orders/Create')
            ->where('selectedFolioId', 5)
        );
    }

    public function test_order_show_renders_menu_and_lines(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('order')->once()->with(3)->andReturn([
                'data' => [
                    'id' => 3,
                    'order_number' => 'ORD-0003',
                    'folio_id' => 5,
                    'status' => 'open',
                    'subtotal' => '900.00',
                    'service_charge_amount' => '90.00',
                    'vat_amount' => '148.50',
                    'total_amount' => '1138.50',
                    'lines' => [[
                        'id' => 1,
                        'menu_item_name' => 'Classic Burger',
                        'quantity' => 2,
                        'unit_price' => '450.00',
                        'line_total' => '900.00',
                    ]],
                ],
            ]);
            $mock->shouldReceive('menuItems')->once()->andReturn(['data' => []]);
            $mock->shouldReceive('folio')->once()->with(5)->andReturn([
                'data' => ['id' => 5, 'status' => 'open', 'balance' => '3000.00'],
            ]);
        });

        $response = $this->get('/fb/orders/3');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/Orders/Show')
            ->where('order.id', 3)
            ->where('folio.id', 5)
        );
    }
}
