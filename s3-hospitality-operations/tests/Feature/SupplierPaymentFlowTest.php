<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class SupplierPaymentFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 88]], 201),
        ]);
    }

    public function test_supplier_payment_posts_ap_settlement_journal(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Fresh Farms PLC',
            'outstanding_balance' => 1000,
            'is_active' => true,
        ]);

        $this->postJson("/api/v1/suppliers/{$supplier->id}/payments", [
            'amount' => 400,
            'payment_method' => 'bank',
        ], array_merge($this->authHeaders(), ['Idempotency-Key' => 'supplier-pay-test-1']))->assertCreated();

        $supplier->refresh();
        $this->assertSame('600.00', (string) $supplier->outstanding_balance);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), '/api/v1/journal-entries')
                && ($body['lines'][0]['account_code'] ?? '') === '2001'
                && ($body['lines'][1]['account_code'] ?? '') === '1002';
        });
    }
}
