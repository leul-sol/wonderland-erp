<?php

namespace Tests\Feature;

use App\Services\CashierShiftService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class FolioPaymentShiftTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 801, 'entry_number' => 'JE-00801']], 201),
        ]);
    }

    public function test_cash_folio_payment_links_to_cashier_shift(): void
    {
        $headers = $this->authHeaders();
        $cashierId = 1;

        $shift = app(CashierShiftService::class)->open($cashierId, 1000.0);

        $room = $this->getJson('/api/v1/rooms', $headers)->json('data.0');
        $reservationId = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'Shift Guest',
            'room_type_id' => $room['room_type']['id'],
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $headers)->json('data.id');

        $folioId = $this->postJson("/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $room['id'],
        ], $headers)->json('data.folio_id');

        $folio = $this->getJson("/api/v1/folios/{$folioId}", $headers)->json('data');
        $balance = (float) ($folio['balance'] ?? $folio['outstanding_balance'] ?? 0);

        $this->postJson("/api/v1/folios/{$folioId}/payments", [
            'amount' => $balance,
            'payment_method' => 'cash',
            'cashier_shift_id' => $shift->id,
        ], array_merge($headers, ['Idempotency-Key' => 'folio-pay-shift-test']))->assertCreated();

        $this->assertDatabaseHas('folio_payments', [
            'folio_id' => $folioId,
            'cashier_shift_id' => $shift->id,
            'payment_method' => 'cash',
        ]);

        $expected = app(CashierShiftService::class)->calculateExpectedCash($shift->fresh());
        $this->assertSame(1000.0 + $balance, $expected);
    }
}
