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
            'S3.inventory.suppliers.write',
            'S3.inventory.purchase_orders.read',
            'S3.inventory.purchase_orders.write',
            'S3.inventory.purchase_orders.approve',
            'S3.inventory.stock.write',
            'S3.inventory.reports.read',
        ]);
    }

    public function test_inventory_items_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inventoryItems')->once()->with(false)->andReturn([
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
            $mock->shouldReceive('itemCategories')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/inventory/items');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Inventory/Items/Index')->has('items', 1));
    }

    public function test_inventory_item_show_renders_stock_and_movements(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inventoryItem')->once()->with(1)->andReturn([
                'data' => [
                    'id' => 1,
                    'sku' => 'BEEF-001',
                    'name' => 'Beef patty',
                    'unit' => 'kg',
                    'quantity_on_hand' => '12.000',
                    'reorder_level' => '5.000',
                    'unit_cost' => '450.00',
                ],
            ]);
            $mock->shouldReceive('inventoryItemStock')->once()->with(1)->andReturn([
                'data' => [
                    'current_stock' => '12.000',
                    'batches' => [[
                        'id' => 9,
                        'batch_code' => 'GR-1-1',
                        'quantity_remaining' => '12.000',
                        'unit_cost' => '450.00',
                        'received_date' => '2026-06-01',
                        'expiry_date' => null,
                    ]],
                ],
            ]);
            $mock->shouldReceive('inventoryItemMovements')->once()->with(1)->andReturn([
                'data' => [
                    'data' => [[
                        'id' => 3,
                        'movement_type' => 'receipt',
                        'quantity' => '12.000',
                        'unit_cost' => '450.00',
                        'reference_type' => 'goods_receipt',
                        'reference_id' => 1,
                        'created_at' => '2026-06-01T10:00:00Z',
                    ]],
                ],
            ]);
        });

        $response = $this->get('/inventory/items/1');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/Items/Show')
            ->where('item.id', 1)
            ->has('movements', 1)
        );
    }

    public function test_stock_alerts_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('lowStockAlerts')->once()->andReturn([
                'data' => [[
                    'id' => 2,
                    'sku' => 'BUN-001',
                    'name' => 'Burger bun',
                    'quantity_on_hand' => '3.000',
                    'reorder_level' => '10.000',
                    'unit' => 'pcs',
                ]],
            ]);
            $mock->shouldReceive('expiryAlerts')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/inventory/alerts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/Alerts/Index')
            ->has('lowStockAlerts', 1)
        );
    }

    public function test_valuation_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('stockValuation')->once()->andReturn([
                'data' => [
                    'total_value' => 5400.00,
                    'lines' => [[
                        'item_id' => 1,
                        'sku' => 'BEEF-001',
                        'batch_id' => 9,
                        'quantity' => 12,
                        'value' => 5400.00,
                    ]],
                ],
            ]);
        });

        $response = $this->get('/inventory/valuation');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/Valuation/Index')
            ->where('totalValue', '5400')
        );
    }

    public function test_supplier_show_exposes_payment_form(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('supplier')->once()->with(4)->andReturn([
                'data' => [
                    'id' => 4,
                    'name' => 'Kitchen Supply',
                    'outstanding_balance' => '15000.00',
                    'payment_terms' => 'Net 30',
                    'is_active' => true,
                ],
            ]);
        });

        $response = $this->get('/inventory/suppliers/4');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/Suppliers/Show')
            ->where('canPay', true)
        );
    }

    public function test_supplier_payment_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('paySupplier')
                ->once()
                ->with(4, \Mockery::on(function (array $payload): bool {
                    return $payload['payment_method'] === 'bank_transfer'
                        && (float) $payload['amount'] === 5000.0;
                }), \Mockery::type('string'))
                ->andReturn(['data' => ['id' => 1]]);
        });

        $response = $this->post('/inventory/suppliers/4/payments', [
            'amount' => '5000',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertRedirect(route('inventory.suppliers.show', 4));
    }

    public function test_purchase_order_create_redirects_to_index(): void
    {
        $response = $this->get('/inventory/purchase-orders/create');

        $response->assertRedirect(route('inventory.purchase-orders.index', ['open' => 'create']));
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
                    'lines' => [[
                        'id' => 11,
                        'sku' => 'BEEF-001',
                        'name' => 'Beef patty',
                        'quantity' => '10.000',
                        'quantity_received' => '0.000',
                        'unit_cost' => '450.00',
                        'line_total' => '4500.00',
                    ]],
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

    public function test_purchase_order_receive_posts_line_quantities(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('receivePurchaseOrder')
                ->once()
                ->with(7, \Mockery::on(function (array $payload): bool {
                    return count($payload['lines'] ?? []) === 1
                        && (int) $payload['lines'][0]['purchase_order_line_id'] === 11
                        && (float) $payload['lines'][0]['quantity_received'] === 5.0;
                }))
                ->andReturn(['data' => ['id' => 7, 'status' => 'partially_received']]);
        });

        $response = $this->post('/inventory/purchase-orders/7/receive', [
            'lines' => [[
                'purchase_order_line_id' => 11,
                'quantity_received' => '5',
            ]],
        ]);

        $response->assertRedirect();
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
