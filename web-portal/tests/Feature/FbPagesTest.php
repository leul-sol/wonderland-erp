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
            'S3.restaurant.billing.write',
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
        $response->assertInertia(fn ($page) => $page->component('Fb/Menu/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('menuItems', 1));
    }

    public function test_orders_index_lists_open_orders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fetchMany')->once()->andReturn([
                'orders' => [
                    'data' => [[
                        'id' => 2,
                        'order_number' => 'ORD-0002',
                        'customer_type' => 'outside_cash',
                        'status' => 'open',
                        'total_amount' => '500.00',
                        'bill' => null,
                    ]],
                ],
                'folios' => ['data' => ['data' => []]],
                'tables' => ['data' => []],
            ]);
        });

        $response = $this->get('/fb/orders?tab=open');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/Orders/Index')
            ->where('filters.tab', 'open')
        );
        $this->assertDeferredInertia($response, fn ($page) => $page->has('pageLoad.orders', 1));
    }

    public function test_order_create_redirects_to_index_with_folio_query(): void
    {
        $response = $this->get('/fb/orders/create?folio_id=5');

        $response->assertRedirect('/fb/orders?open=create&folio_id=5');
    }

    public function test_order_show_renders_menu_and_lines(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('order')->once()->with(3)->andReturn([
                'data' => [
                    'id' => 3,
                    'order_number' => 'ORD-0003',
                    'folio_id' => 5,
                    'customer_type' => 'hotel_guest',
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
                    'bill' => null,
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
            ->where('canPayBill', false)
        );
    }

    public function test_order_show_exposes_bill_payment_when_outstanding(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('order')->once()->with(7)->andReturn([
                'data' => [
                    'id' => 7,
                    'order_number' => 'ORD-0007',
                    'customer_type' => 'outside_credit',
                    'status' => 'finalized',
                    'subtotal' => '450.00',
                    'service_charge_amount' => '45.00',
                    'vat_amount' => '74.25',
                    'total_amount' => '569.25',
                    'lines' => [],
                    'bill' => [
                        'id' => 12,
                        'status' => 'unpaid',
                        'subtotal' => '450.00',
                        'service_charge_amount' => '45.00',
                        'vat_amount' => '74.25',
                        'total_amount' => '569.25',
                        'paid_amount' => '0.00',
                        'outstanding_balance' => '569.25',
                    ],
                ],
            ]);
            $mock->shouldReceive('menuItems')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/fb/orders/7');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/Orders/Show')
            ->where('canPayBill', true)
            ->where('order.bill.id', 12)
        );
    }

    public function test_bill_payment_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('payBill')
                ->once()
                ->with(12, \Mockery::on(function (array $payload): bool {
                    return $payload['payment_method'] === 'cash'
                        && (float) $payload['amount'] === 569.25;
                }), \Mockery::type('string'))
                ->andReturn(['data' => ['id' => 12, 'status' => 'paid']]);
        });

        $response = $this->post('/fb/bills/12/payments', [
            'order_id' => 7,
            'payment_method' => 'cash',
            'amount' => '569.25',
        ]);

        $response->assertRedirect(route('fb.orders.show', 7));
    }
}
