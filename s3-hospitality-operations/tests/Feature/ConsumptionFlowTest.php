<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class ConsumptionFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_key_current' => 'test-service-key',
            'services.s2_url' => 'http://s2.test',
        ]);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 66]], 201),
            'http://s2.test/api/v1/employees/*/deductions' => Http::response([
                'data' => ['id' => 1, 'amount' => '442.75', 'status' => 'applied'],
            ], 201),
        ]);
    }

    public function test_consumption_close_posts_deduction_from_order_totals(): void
    {
        $headers = $this->authHeaders();
        $this->receiveStock();

        $periodId = $this->postJson('/api/v1/employee-consumption-periods', [
            'employee_id' => 42,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ], $headers)->assertCreated()->json('data.id');

        $burger = MenuItem::query()->where('code', 'BURGER-CL')->firstOrFail();
        $orderId = $this->postJson('/api/v1/orders', [
            'employee_consumption_period_id' => $periodId,
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/lines", [
            'menu_item_id' => $burger->id,
            'quantity' => 1,
        ], $headers);

        $this->postJson("/api/v1/orders/{$orderId}/finalize", [], $headers)->assertOk();

        $this->postJson("/api/v1/employee-consumption-periods/{$periodId}/close", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.total_amount', '442.75');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/deductions')
            && $request->hasHeader('X-Service-Key', 'test-service-key'));

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s3.employee_consumption_period.closed',
        ]);
    }

    private function receiveStock(): void
    {
        $beef = InventoryItem::query()->where('sku', 'BEEF-001')->firstOrFail();
        $bun = InventoryItem::query()->where('sku', 'BUN-001')->firstOrFail();
        $headers = $this->authHeaders();

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Kitchen Supply',
            'lines' => [
                ['inventory_item_id' => $beef->id, 'quantity' => 10, 'unit_cost' => 450],
                ['inventory_item_id' => $bun->id, 'quantity' => 50, 'unit_cost' => 15],
            ],
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $headers);
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [], $headers);
    }
}
