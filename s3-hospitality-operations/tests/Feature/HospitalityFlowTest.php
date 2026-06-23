<?php

namespace Tests\Feature;

use App\Models\EventOutbox;
use App\Models\Room;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class HospitalityFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 99, 'entry_number' => 'JE-00001']], 201),
        ]);
    }

    public function test_golden_path_check_in_charge_settle_check_out(): void
    {
        $room = Room::query()->where('room_number', '101')->firstOrFail();
        $checkIn = now()->toDateString();
        $checkOut = now()->addDays(2)->toDateString();

        $reservation = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'Abebe Kebede',
            'guest_email' => 'abebe@example.com',
            'room_type_id' => $room->room_type_id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
        ], $this->authHeaders());

        $reservation->assertCreated();
        $reservationId = $reservation->json('data.id');

        $checkedIn = $this->postJson("/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $room->id,
        ], $this->authHeaders());

        $checkedIn->assertOk()
            ->assertJsonPath('data.status', 'checked_in');

        $folioId = $checkedIn->json('data.folio_id');
        $this->assertNotNull($folioId);

        $this->assertDatabaseHas('event_outbox', [
            'event' => 'wh.events.s3.guest.checked_in',
        ]);

        // SDD §5.6: check-in auto-posts room rent per night (2 nights × 2500 + SC/VAT = 6325.00)
        $folio = $this->getJson("/api/v1/folios/{$folioId}", $this->authHeaders());
        $folio->assertOk()
            ->assertJsonPath('data.total_charges', '6325.00');

        $charge = $this->postJson("/api/v1/folios/{$folioId}/charges", [
            'description' => 'Minibar snack',
            'amount' => 150,
            'charge_category' => 'other',
        ], $this->authHeaders());

        $charge->assertCreated()
            ->assertJsonPath('data.total_charges', '6514.75');

        $settled = $this->postJson("/api/v1/folios/{$folioId}/settle", [
            'amount' => 6514.75,
            'payment_method' => 'cash',
        ], $this->authHeaders());

        $settled->assertOk()
            ->assertJsonPath('data.status', 'settled')
            ->assertJsonPath('data.balance', '0.00');

        $checkedOut = $this->postJson("/api/v1/reservations/{$reservationId}/check-out", [], $this->authHeaders());

        $checkedOut->assertOk()
            ->assertJsonPath('data.status', 'checked_out');

        $this->assertSame(3, EventOutbox::query()->count());
    }

    public function test_cannot_check_out_with_unsettled_folio(): void
    {
        $room = Room::query()->where('room_number', '102')->firstOrFail();

        $reservation = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'Sara Tesfaye',
            'room_type_id' => $room->room_type_id,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $this->authHeaders())->json('data.id');

        $this->postJson("/api/v1/reservations/{$reservation}/check-in", ['room_id' => $room->id], $this->authHeaders());

        $response = $this->postJson("/api/v1/reservations/{$reservation}/check-out", [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}
