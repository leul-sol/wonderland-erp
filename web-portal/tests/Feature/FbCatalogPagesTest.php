<?php

namespace Tests\Feature;

use App\Services\Api\S3HospitalityClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class FbCatalogPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.user', ['username' => 'restaurant.manager', 'name' => 'Manager']);
        Session::put('portal.permissions', [
            'S3.restaurant.menu.read',
            'S3.restaurant.menu.write',
        ]);
    }

    public function test_catalog_settings_hub_renders(): void
    {
        $response = $this->get('/fb/settings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Fb/Settings/Index'));
    }

    public function test_menu_categories_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('menuCategories')->once()->with(false)->andReturn([
                'data' => [[
                    'id' => 1,
                    'name' => 'Mains',
                    'display_order' => 1,
                    'is_active' => true,
                ]],
            ]);
        });

        $response = $this->get('/fb/menu-categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/MenuCategories/Index')
            ->has('categories', 1)
        );
    }

    public function test_menu_items_admin_index_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('menuItemsCatalog')->once()->with(false)->andReturn([
                'data' => [[
                    'id' => 2,
                    'code' => 'BURGER-CL',
                    'name' => 'Classic Burger',
                    'price' => '450.00',
                    'employee_price' => '200.00',
                    'category' => 'Mains',
                    'has_recipe' => true,
                    'is_active' => true,
                ]],
            ]);
        });

        $response = $this->get('/fb/menu-items');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/MenuItems/Index')
            ->has('menuItems', 1)
        );
    }

    public function test_menu_item_create_page_renders_categories(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('menuCategories')->once()->with(false)->andReturn([
                'data' => [['id' => 1, 'name' => 'Mains']],
            ]);
        });

        $response = $this->get('/fb/menu-items/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/MenuItems/Create')
            ->has('categories', 1)
        );
    }

    public function test_menu_item_edit_page_renders_recipe_data(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('menuItem')->once()->with(2)->andReturn([
                'data' => [
                    'id' => 2,
                    'code' => 'BURGER-CL',
                    'name' => 'Classic Burger',
                    'price' => '450.00',
                    'employee_price' => '200.00',
                    'category_id' => 1,
                    'is_active' => true,
                    'ingredients' => [[
                        'inventory_item_id' => 5,
                        'sku' => 'BEEF-001',
                        'name' => 'Beef patty',
                        'quantity' => '0.150',
                    ]],
                ],
            ]);
            $mock->shouldReceive('menuCategories')->once()->with(false)->andReturn(['data' => []]);
            $mock->shouldReceive('inventoryItems')->once()->andReturn([
                'data' => [['id' => 5, 'sku' => 'BEEF-001', 'name' => 'Beef patty']],
            ]);
        });

        $response = $this->get('/fb/menu-items/2/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/MenuItems/Edit')
            ->where('menuItem.id', 2)
            ->has('menuItem.ingredients', 1)
        );
    }

    public function test_menu_item_update_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateMenuItem')
                ->once()
                ->with(2, \Mockery::on(function (array $payload): bool {
                    return $payload['name'] === 'Classic Burger'
                        && $payload['is_available'] === false;
                }))
                ->andReturn(['data' => ['id' => 2]]);
        });

        $response = $this->put('/fb/menu-items/2', [
            'name' => 'Classic Burger',
            'price' => '450',
            'employee_price' => '200',
            'category_id' => 1,
            'is_available' => false,
        ]);

        $response->assertRedirect();
    }

    public function test_menu_item_recipe_update_posts_to_s3(): void
    {
        $this->withoutMiddleware();

        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('updateMenuItemRecipe')
                ->once()
                ->with(2, \Mockery::on(function (array $payload): bool {
                    return count($payload['ingredients'] ?? []) === 1
                        && (int) $payload['ingredients'][0]['inventory_item_id'] === 5;
                }))
                ->andReturn(['data' => ['id' => 2, 'has_recipe' => true]]);
        });

        $response = $this->put('/fb/menu-items/2/recipe', [
            'ingredients' => [[
                'inventory_item_id' => 5,
                'quantity' => '0.15',
            ]],
        ]);

        $response->assertRedirect();
    }

    public function test_dining_tables_page_renders(): void
    {
        $this->mock(S3HospitalityClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('diningTables')->once()->with(false)->andReturn([
                'data' => [[
                    'id' => 3,
                    'table_number' => 'T-01',
                    'capacity' => 4,
                    'location' => 'Terrace',
                    'is_active' => true,
                ]],
            ]);
        });

        $response = $this->get('/fb/dining-tables');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Fb/DiningTables/Index')
            ->has('tables', 1)
        );
    }
}
