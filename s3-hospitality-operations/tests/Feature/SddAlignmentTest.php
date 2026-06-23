<?php

namespace Tests\Feature;

use App\Models\GuestProfile;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\MenuItem;
use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class SddAlignmentTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal_key_current' => 'test-service-key']);
        $this->seed(DatabaseSeeder::class);
        \Illuminate\Support\Facades\Http::fake([
            '*/api/v1/journal-entries' => \Illuminate\Support\Facades\Http::response(['data' => ['id' => 1]], 201),
        ]);
    }

    public function test_item_category_crud(): void
    {
        $headers = $this->authHeaders();

        $created = $this->postJson('/api/v1/item-categories', [
            'name' => 'Dry Goods',
            'description' => 'Non-perishables',
        ], $headers)->assertCreated();

        $id = $created->json('data.id');

        $this->getJson('/api/v1/item-categories', $headers)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Dry Goods']);

        $this->putJson("/api/v1/item-categories/{$id}", ['name' => 'Dry Goods Updated'], $headers)
            ->assertOk();
    }

    public function test_supplier_crud(): void
    {
        $headers = $this->authHeaders();

        $this->postJson('/api/v1/suppliers', [
            'name' => 'Fresh Farms PLC',
            'payment_terms' => 'Net 30',
        ], $headers)->assertCreated();

        $this->getJson('/api/v1/suppliers', $headers)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Fresh Farms PLC']);
    }

    public function test_guest_profile_and_folio_invoice_fields(): void
    {
        $headers = $this->authHeaders();

        $guest = $this->postJson('/api/v1/guest-profiles', [
            'full_name' => 'Helen Assefa',
            'phone' => '+251911000000',
            'email' => 'helen@example.com',
            'id_document_type' => 'Passport',
            'id_document_number' => 'P123456',
            'nationality' => 'Ethiopian',
        ], $headers)->assertCreated()->json('data');

        $this->assertDatabaseHas('guest_profiles', ['id' => $guest['id']]);

        $room = $this->getJson('/api/v1/rooms', $headers)->json('data.0');

        $reservationId = $this->postJson('/api/v1/reservations', [
            'guest_id' => $guest['id'],
            'guest_name' => 'Helen Assefa',
            'room_type_id' => $room['room_type']['id'],
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $headers)->json('data.id');

        $folioId = $this->postJson("/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $room['id'],
        ], $headers)->json('data.folio_id');

        $invoice = $this->getJson("/api/v1/folios/{$folioId}/invoice", $headers)->assertOk()->json('data');

        $this->assertSame('Helen Assefa', $invoice['guest_full_name']);
        $this->assertArrayHasKey('folio_number', $invoice);
        $this->assertArrayHasKey('total_charges', $invoice);
    }

    public function test_stock_valuation_endpoint(): void
    {
        ItemCategory::query()->create(['name' => 'Test Cat', 'is_active' => true]);

        $this->getJson('/api/v1/stock/valuation', $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['data' => ['total_value', 'lines']]);
    }

    public function test_purchase_order_cancel(): void
    {
        $headers = $this->authHeaders();
        $beef = $this->getJson('/api/v1/items', $headers)->json('data.0.id');

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Cancel Vendor',
            'lines' => [
                ['inventory_item_id' => $beef, 'quantity' => 1, 'unit_cost' => 100],
            ],
        ], $headers)->json('data.id');

        $this->putJson("/api/v1/purchase-orders/{$poId}/cancel", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_menu_recipe_update(): void
    {
        $headers = $this->authHeaders();
        $burger = MenuItem::query()->where('code', 'BURGER-CL')->firstOrFail();
        $beef = InventoryItem::query()->where('sku', 'BEEF-001')->firstOrFail();
        $bun = InventoryItem::query()->where('sku', 'BUN-001')->firstOrFail();

        $this->putJson("/api/v1/menu-items/{$burger->id}/recipe", [
            'ingredients' => [
                ['inventory_item_id' => $beef->id, 'quantity' => 0.2],
                ['inventory_item_id' => $bun->id, 'quantity' => 1],
            ],
        ], $headers)->assertOk()
            ->assertJsonCount(2, 'data.ingredients');
    }

    public function test_order_cancel_and_remove_line(): void
    {
        $headers = $this->authHeaders();
        $burger = MenuItem::query()->where('code', 'BURGER-CL')->firstOrFail();

        $orderId = $this->postJson('/api/v1/orders', [], $headers)->json('data.id');

        $lineId = $this->postJson("/api/v1/orders/{$orderId}/lines", [
            'menu_item_id' => $burger->id,
            'quantity' => 1,
        ], $headers)->json('data.lines.0.id');

        $this->deleteJson("/api/v1/orders/{$orderId}/lines/{$lineId}", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.subtotal', '0.00');

        $this->putJson("/api/v1/orders/{$orderId}/cancel", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_bill_marked_paid_on_finalize(): void
    {
        $headers = $this->authHeaders();
        $cola = MenuItem::query()->where('code', 'DRINK-COLA')->firstOrFail();
        $colaItem = InventoryItem::query()->where('sku', 'COLA-330')->firstOrFail();

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Beverage Co',
            'lines' => [
                ['inventory_item_id' => $colaItem->id, 'quantity' => 24, 'unit_cost' => 25],
            ],
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($headers, 'po-approve-bill-'.$poId));
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [], $headers);

        $orderId = $this->postJson('/api/v1/orders', [], $headers)->json('data.id');
        $this->postJson("/api/v1/orders/{$orderId}/lines", [
            'menu_item_id' => $cola->id,
            'quantity' => 1,
        ], $headers);

        $this->postJson("/api/v1/orders/{$orderId}/finalize", [], $headers)->assertOk();

        $this->assertDatabaseHas('bills', [
            'restaurant_order_id' => $orderId,
            'status' => 'paid',
            'outstanding_balance' => 0,
        ]);
    }

    public function test_expiry_alert_command_queues_outbox_event(): void
    {
        $beef = InventoryItem::query()->where('sku', 'BEEF-001')->firstOrFail();

        \App\Models\StockBatch::query()->create([
            'inventory_item_id' => $beef->id,
            'batch_code' => 'EXP-TEST',
            'quantity_received' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 100,
            'received_date' => now()->subDays(10)->toDateString(),
            'expiry_date' => now()->addDays(3)->toDateString(),
            'status' => 'active',
        ]);

        $this->artisan('stock:expiry-alerts')->assertSuccessful();

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s3.stock.expiry_alert',
        ]);
    }

    public function test_consumption_push_command_posts_to_s2(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/v1/employees/*/deductions' => \Illuminate\Support\Facades\Http::response(['data' => ['id' => 1]], 201),
        ]);

        \App\Models\EmployeeConsumption::query()->create([
            'employee_id' => 42,
            'period' => now()->format('Y-m'),
            'total_amount' => 150,
            'pushed_to_payroll' => false,
        ]);

        $this->artisan('consumption:push-to-payroll')->assertSuccessful();

        $this->assertDatabaseHas('employee_consumption', [
            'employee_id' => 42,
            'pushed_to_payroll' => true,
        ]);
    }
}
