<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class InventoryBackOfficePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S3.inventory.items.read',
            'S3.inventory.items.write',
            'S3.inventory.stock.write',
            'S3.inventory.suppliers.read',
            'S3.inventory.suppliers.write',
            'S3.inventory.stock.read',
        ]);
    }

    public function test_item_categories_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('itemCategories')->once()->andReturn([
                'data' => [['id' => 1, 'name' => 'Dry Goods']],
            ]);
        });

        $response = $this->get('/inventory/item-categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Inventory/ItemCategories/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('categories', 1));
    }

    public function test_inventory_item_create_redirects_to_index(): void
    {
        $response = $this->get('/inventory/items/create');

        $response->assertRedirect(route('inventory.items.index', ['open' => 'create']));
    }

    public function test_create_inventory_item_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInventoryItem')
                ->once()
                ->with(\Mockery::on(fn (array $payload): bool => $payload['sku'] === 'SKU-1'))
                ->andReturn(['data' => ['id' => 9]]);
        });

        $response = $this->post('/inventory/items', [
            'sku' => 'SKU-1',
            'name' => 'Test item',
            'unit' => 'kg',
        ]);

        $response->assertRedirect(route('inventory.items.show', 9));
    }

    public function test_stock_adjustment_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('adjustStock')
                ->once()
                ->with([
                    'inventory_item_id' => 3,
                    'quantity' => 5.0,
                    'reason' => 'Count correction',
                ])
                ->andReturn(['data' => ['id' => 1]]);
        });

        $response = $this->post('/inventory/items/3/adjust', [
            'quantity' => '5',
            'reason' => 'Count correction',
        ]);

        $response->assertRedirect();
    }

    public function test_supplier_create_redirects_to_index(): void
    {
        $response = $this->get('/inventory/suppliers/create');

        $response->assertRedirect(route('inventory.suppliers.index', ['open' => 'create']));
    }

    public function test_create_supplier_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSupplier')
                ->once()
                ->andReturn(['data' => ['id' => 4, 'name' => 'Acme']]);
        });

        $response = $this->post('/inventory/suppliers', [
            'name' => 'Acme Supplies',
        ]);

        $response->assertRedirect(route('inventory.suppliers.show', 4));
    }

    public function test_goods_receipt_show_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('goodsReceipt')->once()->with(7)->andReturn([
                'data' => ['id' => 7, 'lines' => []],
            ]);
        });

        $response = $this->get('/inventory/goods-receipts/7');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/GoodsReceipts/Show')
            ->where('goodsReceipt.id', 7)
        );
    }
}
