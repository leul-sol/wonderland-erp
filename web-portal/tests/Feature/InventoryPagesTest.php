<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use App\Services\Api\S4FinanceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class InventoryPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['username' => 'inventory.manager', 'name' => 'Inventory']);
        Session::put('portal.permissions', [
            'S3.inventory.items.read',
            'S3.inventory.suppliers.read',
            'S3.inventory.purchase_orders.read',
            'S3.inventory.purchase_orders.write',
            'S3.inventory.purchase_orders.approve',
            'S3.inventory.stock.write',
        ]);
    }

    public function test_inventory_items_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inventoryItems')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'sku' => 'BEEF-001',
                    'name' => 'Beef patty',
                    'unit' => 'kg',
                    'quantity_on_hand' => '12.000',
                    'reorder_level' => '5.000',
                    'unit_cost' => '450.00',
                ]],
            ]);
        });

        $response = $this->get('/inventory/items');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Inventory/Items/Index')->has('items', 1));
    }

    public function test_purchase_order_create_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inventoryItems')->once()->andReturn(['data' => []]);
            $mock->shouldReceive('suppliers')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/inventory/purchase-orders/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Inventory/PurchaseOrders/Create'));
    }

    public function test_purchase_order_show_includes_workflow_flags(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('purchaseOrder')->once()->with(7)->andReturn([
                'data' => [
                    'id' => 7,
                    'po_number' => 'PO-0007',
                    'vendor_name' => 'Kitchen Supply',
                    'status' => 'approved',
                    'approval_tier' => 2,
                    'total_amount' => '15000.00',
                    'lines' => [],
                ],
            ]);
        });

        $response = $this->get('/inventory/purchase-orders/7');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/PurchaseOrders/Show')
            ->where('canReceive', true)
            ->where('canSubmit', false)
        );
    }

    public function test_payables_page_renders_open_balances(): void
    {
        Session::put('portal.permissions', [
            'S4.finance.payables.read',
            'S4.finance.payables.settle',
        ]);

        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('payables')->once()->with('open')->andReturn([
                'data' => [[
                    'id' => 3,
                    'vendor_name' => 'Kitchen Supply',
                    'source_reference' => 'PO-0001',
                    'status' => 'open',
                    'balance' => '5000.00',
                ]],
            ]);
        });

        $response = $this->get('/finance/payables');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/Payables/Index')->has('payables', 1));
    }
}
