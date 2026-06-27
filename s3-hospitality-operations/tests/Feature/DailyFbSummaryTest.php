<?php

namespace Tests\Feature;

use App\Models\FbDailySummary;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\Room;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class DailyFbSummaryTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response([
                'data' => ['id' => 501, 'entry_number' => 'JE-00501'],
            ], 201),
        ]);
    }

    public function test_cash_order_defers_revenue_until_daily_summary_batch(): void
    {
        $this->receiveColaStock();

        $cola = MenuItem::query()->where('code', 'DRINK-COLA')->firstOrFail();
        $orderId = $this->postJson('/api/v1/orders', [], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/lines", [
            'menu_item_id' => $cola->id,
            'quantity' => 1,
        ], $this->authHeaders())->assertCreated();

        $this->postJson("/api/v1/orders/{$orderId}/finalize", [], $this->authHeaders())->assertOk();

        $order = RestaurantOrder::query()->findOrFail($orderId);
        $this->assertNull($order->revenue_journal_entry_id);
        $this->assertNotNull($order->cogs_journal_entry_id);

        Http::assertNotSent(function ($request) {
            if (! str_contains($request->url(), '/api/v1/journal-entries')) {
                return false;
            }

            $body = $request->data();

            return ($body['lines'][1]['account_code'] ?? '') === '4002';
        });

        Artisan::call('fb:daily-summary', ['--date' => now()->toDateString()]);

        $order->refresh();
        $this->assertSame('501', $order->revenue_journal_entry_id);

        $summary = FbDailySummary::query()->firstOrFail();
        $this->assertSame(1, $summary->order_count);
        $this->assertSame('501', $summary->s4_journal_entry_id);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/v1/journal-entries')) {
                return false;
            }

            $body = $request->data();
            if (($body['source_reference'] ?? '') !== 'FB-DAILY:'.now()->toDateString()) {
                return false;
            }

            $codes = collect($body['lines'] ?? [])->pluck('account_code');

            return $codes->contains('1001') && $codes->contains('4002');
        });
    }

    public function test_daily_summary_excludes_hotel_guest_folio_orders(): void
    {
        $this->receiveBurgerStock();

        $room = Room::query()->where('room_number', '101')->firstOrFail();
        $reservationId = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'In-house guest',
            'room_type_id' => $room->room_type_id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $this->authHeaders())->json('data.id');

        $folioId = $this->postJson("/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $room->id,
        ], $this->authHeaders())->json('data.folio_id');

        $burger = MenuItem::query()->where('code', 'BURGER-CL')->firstOrFail();
        $orderId = $this->postJson('/api/v1/orders', ['folio_id' => $folioId], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/lines", [
            'menu_item_id' => $burger->id,
            'quantity' => 1,
        ], $this->authHeaders());

        $this->postJson("/api/v1/orders/{$orderId}/finalize", [], $this->authHeaders())->assertOk();

        Artisan::call('fb:daily-summary', ['--date' => now()->toDateString()]);

        $this->assertDatabaseCount('fb_daily_summaries', 0);

        $guestOrder = RestaurantOrder::query()->findOrFail($orderId);
        $this->assertNotNull($guestOrder->revenue_journal_entry_id);
    }

    private function receiveColaStock(): void
    {
        $colaItem = InventoryItem::query()->where('sku', 'COLA-330')->firstOrFail();
        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Beverage Co',
            'lines' => [
                ['inventory_item_id' => $colaItem->id, 'quantity' => 24, 'unit_cost' => 25],
            ],
        ], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($this->authHeaders(), 'daily-fb-po-approve'));
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [], $this->authHeaders());
    }

    private function receiveBurgerStock(): void
    {
        $beef = InventoryItem::query()->where('sku', 'BEEF-001')->firstOrFail();
        $bun = InventoryItem::query()->where('sku', 'BUN-001')->firstOrFail();

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Kitchen Supply',
            'lines' => [
                ['inventory_item_id' => $beef->id, 'quantity' => 10, 'unit_cost' => 450],
                ['inventory_item_id' => $bun->id, 'quantity' => 50, 'unit_cost' => 15],
            ],
        ], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($this->authHeaders(), 'daily-fb-burger-po'));
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [], $this->authHeaders());
    }
}
