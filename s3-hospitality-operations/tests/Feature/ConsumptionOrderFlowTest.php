<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class ConsumptionOrderFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 77]], 201),
            'http://s2.test/api/v1/employees/*/deductions' => Http::response([
                'data' => ['id' => 1, 'amount' => '350.00', 'status' => 'applied'],
            ], 201),
        ]);
    }

    public function test_consumption_period_total_sums_finalized_employee_meal_orders(): void
    {
        config(['services.s2_url' => 'http://s2.test']);
        $headers = $this->authHeaders();

        $this->receiveStock();

        $period = $this->postJson('/api/v1/employee-consumption-periods', [
            'employee_id' => 42,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ], $headers)->assertCreated();

        $periodId = $period->json('data.id');
        $this->assertSame('0.00', $period->json('data.total_amount'));

        $burger = MenuItem::query()->where('code', 'BURGER-CL')->firstOrFail();

        $order = $this->postJson('/api/v1/orders', [
            'employee_consumption_period_id' => $periodId,
        ], $headers)->assertCreated()->json('data.id');

        $this->postJson("/api/v1/orders/{$order}/lines", [
            'menu_item_id' => $burger->id,
            'quantity' => 1,
        ], $headers)->assertCreated();

        $this->postJson("/api/v1/orders/{$order}/finalize", [], $headers)->assertOk();

        $this->postJson("/api/v1/employee-consumption-periods/{$periodId}/close", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.total_amount', '350.00');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/deductions'));
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
