<?php

namespace Tests\Feature;

use App\Services\TaxBreakdownService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class TaxBreakdownFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 501, 'entry_number' => 'JE-00501']], 201),
        ]);
    }

    public function test_tax_breakdown_computes_sc_and_vat(): void
    {
        $breakdown = app(TaxBreakdownService::class)->compute(1000.0);

        $this->assertSame(1000.0, $breakdown['subtotal']);
        $this->assertSame(100.0, $breakdown['service_charge_amount']);
        $this->assertSame(165.0, $breakdown['vat_amount']);
        $this->assertSame(1265.0, $breakdown['total_amount']);
    }

    public function test_folio_charge_posts_revenue_sc_and_vat_lines(): void
    {
        $headers = $this->authHeaders();

        $room = $this->getJson('/api/v1/rooms', $headers)->json('data.0');
        $reservationId = $this->postJson('/api/v1/reservations', [
            'guest_name' => 'Tax Guest',
            'room_type_id' => $room['room_type']['id'],
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addDay()->toDateString(),
        ], $headers)->json('data.id');

        $folioId = $this->postJson("/api/v1/reservations/{$reservationId}/check-in", [
            'room_id' => $room['id'],
        ], $headers)->json('data.folio_id');

        $response = $this->postJson("/api/v1/folios/{$folioId}/charges", [
            'description' => 'Room night',
            'amount' => 1000,
            'charge_category' => 'room',
        ], $headers);

        $response->assertCreated();

        $chargeLine = collect($response->json('data.lines'))
            ->where('line_type', 'charge')
            ->last();

        $this->assertSame('1000.00', $chargeLine['subtotal']);
        $this->assertSame('100.00', $chargeLine['service_charge_amount']);
        $this->assertSame('165.00', $chargeLine['vat_amount']);
        $this->assertSame('1265.00', $chargeLine['amount']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $lines = $body['lines'] ?? [];

            return str_contains($request->url(), '/api/v1/journal-entries')
                && count($lines) === 4
                && ($lines[0]['account_code'] ?? '') === '1100'
                && (float) ($lines[0]['debit'] ?? 0) === 1265.0
                && ($lines[2]['account_code'] ?? '') === '4003'
                && ($lines[3]['account_code'] ?? '') === '2300';
        });
    }
}
