<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class GroupBookingFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.internal_key_current' => 'test-service-key']);
        $this->seed(DatabaseSeeder::class);
        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 88, 'entry_number' => 'JE-00088']], 201),
        ]);
    }

    public function test_group_booking_create_check_in_and_check_out(): void
    {
        $headers = $this->authHeaders();
        $roomType = RoomType::query()->firstOrFail();
        $room101 = Room::query()->where('room_number', '101')->firstOrFail();
        $room102 = Room::query()->where('room_number', '102')->firstOrFail();

        $group = $this->postJson('/api/v1/group-bookings', [
            'group_name' => 'Corporate Retreat',
            'contact_name' => 'Event Planner',
            'contact_email' => 'events@corp.test',
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'rooms' => [
                ['guest_name' => 'Guest A', 'room_type_id' => $roomType->id],
                ['guest_name' => 'Guest B', 'room_type_id' => $roomType->id],
            ],
        ], $headers);

        $group->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.room_count', 2);

        $groupId = $group->json('data.id');
        $reservations = $group->json('data.reservations');

        $checkedIn = $this->postJson("/api/v1/group-bookings/{$groupId}/check-in", [
            'assignments' => [
                ['reservation_id' => $reservations[0]['id'], 'room_id' => $room101->id],
                ['reservation_id' => $reservations[1]['id'], 'room_id' => $room102->id],
            ],
        ], $headers);

        $checkedIn->assertOk()
            ->assertJsonPath('data.status', 'checked_in');

        foreach ($checkedIn->json('data.reservations') as $reservation) {
            $folioId = $reservation['folio_id'] ?? null;
            if ($folioId !== null) {
                $folio = $this->getJson("/api/v1/folios/{$folioId}", $headers)->json('data');
                $balance = (float) ($folio['balance'] ?? $folio['total_charges'] ?? 0);

                $this->postJson("/api/v1/folios/{$folioId}/settle", [
                    'amount' => $balance,
                    'payment_method' => 'cash',
                ], $headers)->assertOk();
            }
        }

        $this->postJson("/api/v1/group-bookings/{$groupId}/check-out", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'checked_out');
    }
}
