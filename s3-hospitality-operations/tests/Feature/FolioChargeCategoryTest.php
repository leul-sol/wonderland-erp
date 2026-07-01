<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class FolioChargeCategoryTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 901, 'entry_number' => 'JE-00901']], 201),
        ]);
    }

    public function test_incidental_categories_accepted_by_api(): void
    {
        $headers = $this->authHeaders();

        $room = $this->getJson('/api/v1/rooms', $headers)->json('data.0');
        $reservationId = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'Laundry Guest',
            'room_type_id' => $room['room_type']['id'],
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $headers)->json('data.id');

        $folioId = $this->postJson("/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $room['id'],
        ], $headers)->json('data.folio_id');

        foreach (['laundry', 'minibar', 'event'] as $category) {
            $this->postJson("/api/v1/folios/{$folioId}/charges", [
                'description' => ucfirst($category).' charge',
                'amount' => 100,
                'charge_category' => $category,
            ], $headers)->assertCreated();
        }

        $this->assertDatabaseHas('folio_lines', [
            'folio_id' => $folioId,
            'charge_category' => 'laundry',
            'description' => 'Laundry charge',
        ]);
    }
}
