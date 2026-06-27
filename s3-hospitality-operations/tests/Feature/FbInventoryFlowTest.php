<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\Room;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class FbInventoryFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    private int $journalSequence = 100;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake(function () {
            $this->journalSequence++;

            return Http::response([
                'data' => ['id' => $this->journalSequence, 'entry_number' => 'JE-'.str_pad((string) $this->journalSequence, 5, '0', STR_PAD_LEFT)],
            ], 201);
        });
    }

    public function test_goods_received_posts_inventory_journal(): void
    {
        $beef = InventoryItem::query()->where('sku', 'BEEF-001')->firstOrFail();
        $bun = InventoryItem::query()->where('sku', 'BUN-001')->firstOrFail();

        $po = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Fresh Foods PLC',
            'lines' => [
                ['inventory_item_id' => $beef->id, 'quantity' => 10, 'unit_cost' => 450],
                ['inventory_item_id' => $bun->id, 'quantity' => 50, 'unit_cost' => 15],
            ],
        ], $this->authHeaders());

        $po->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total_amount', '5250.00');

        $poId = $po->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($this->authHeaders(), 'po-approve-'.$poId))->assertOk();

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/v1/journal-entries')
                && ($body['lines'][0]['account_code'] ?? '') === '1200'
                && ($body['lines'][1]['account_code'] ?? '') === '2001';
        });

        $received = $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [], $this->authHeaders());
        $received->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $beef->refresh();
        $bun->refresh();
        $this->assertSame('10.000', (string) $beef->quantity_on_hand);
        $this->assertSame('50.000', (string) $bun->quantity_on_hand);

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s3.goods.received',
        ]);
    }

    public function test_finalize_folio_order_posts_revenue_and_cogs(): void
    {
        $beef = InventoryItem::query()->where('sku', 'BEEF-001')->firstOrFail();
        $bun = InventoryItem::query()->where('sku', 'BUN-001')->firstOrFail();

        $this->receiveStock($beef, $bun);

        $room = Room::query()->where('room_number', '101')->firstOrFail();
        $reservation = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'Guest Diner',
            'room_type_id' => $room->room_type_id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $this->authHeaders())->json('data.id');

        $checkedIn = $this->postJson("/api/v1/reservations/{$reservation}/check-in", [
            'room_id' => $room->id,
        ], $this->authHeaders());

        $folioId = $checkedIn->json('data.folio_id');
        $burger = MenuItem::query()->where('code', 'BURGER-CL')->firstOrFail();

        $order = $this->postJson('/api/v1/orders', ['folio_id' => $folioId], $this->authHeaders());
        $order->assertCreated();
        $orderId = $order->json('data.id');

        $this->postJson("/api/v1/orders/{$orderId}/lines", [
            'menu_item_id' => $burger->id,
            'quantity' => 2,
        ], $this->authHeaders())->assertCreated();

        $finalized = $this->postJson("/api/v1/orders/{$orderId}/finalize", [], $this->authHeaders());
        $finalized->assertOk()
            ->assertJsonPath('data.status', 'finalized')
            ->assertJsonPath('data.subtotal', '700.00')
            ->assertJsonPath('data.cogs_total', '210.00');

        $beef->refresh();
        $bun->refresh();
        $this->assertSame('9.600', (string) $beef->quantity_on_hand);
        $this->assertSame('48.000', (string) $bun->quantity_on_hand);

        $folio = $this->getJson("/api/v1/folios/{$folioId}", $this->authHeaders());
        $folio->assertOk()
            ->assertJsonPath('data.total_charges', '4048.00');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/v1/journal-entries')
                && ($body['lines'][1]['account_code'] ?? '') === '4002';
        });

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/v1/journal-entries')
                && ($body['lines'][0]['account_code'] ?? '') === '5003'
                && ($body['lines'][1]['account_code'] ?? '') === '1200';
        });

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s3.order.finalized',
        ]);
    }

    public function test_cash_order_posts_cash_revenue_without_folio(): void
    {
        $colaItem = InventoryItem::query()->where('sku', 'COLA-330')->firstOrFail();
        $po = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Beverage Co',
            'lines' => [
                ['inventory_item_id' => $colaItem->id, 'quantity' => 24, 'unit_cost' => 25],
            ],
        ], $this->authHeaders());

        $poId = $po->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($this->authHeaders(), 'po-approve-cash-'.$poId));
        $this->postJson("/api/v1/purchase-orders/{$poId}/receive", [], $this->authHeaders());

        $cola = MenuItem::query()->where('code', 'DRINK-COLA')->firstOrFail();
        $order = $this->postJson('/api/v1/orders', [], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/orders/{$order}/lines", [
            'menu_item_id' => $cola->id,
            'quantity' => 1,
        ], $this->authHeaders());

        $this->postJson("/api/v1/orders/{$order}/finalize", [], $this->authHeaders())->assertOk();

        $orderModel = \App\Models\RestaurantOrder::query()->findOrFail($order);
        $this->assertNull($orderModel->revenue_journal_entry_id);

        \Illuminate\Support\Facades\Artisan::call('fb:daily-summary', ['--date' => now()->toDateString()]);

        $orderModel->refresh();
        $this->assertNotNull($orderModel->revenue_journal_entry_id);

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

    private function receiveStock(InventoryItem $beef, InventoryItem $bun): void
    {
        $po = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Kitchen Supply',
            'lines' => [
                ['inventory_item_id' => $beef->id, 'quantity' => 10, 'unit_cost' => 450],
                ['inventory_item_id' => $bun->id, 'quantity' => 50, 'unit_cost' => 15],
            ],
        ], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$po}/approve", [], $this->withIdempotency($this->authHeaders(), 'po-approve-stock-'.$po));
        $this->postJson("/api/v1/purchase-orders/{$po}/receive", [], $this->authHeaders());
    }
}
