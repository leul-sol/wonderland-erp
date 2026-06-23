<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS3Auth;
use Tests\TestCase;

class PurchaseOrderApprovalFlowTest extends TestCase
{
    use MocksS3Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.internal_key_current' => 'test-service-key']);

        $this->seed(DatabaseSeeder::class);

        Http::fake([
            '*/api/v1/journal-entries' => Http::response(['data' => ['id' => 77, 'entry_number' => 'JE-00077']], 201),
        ]);
    }

    public function test_tier1_po_approves_after_department_head_step(): void
    {
        $headers = $this->authHeaders(roles: ['department_head']);

        $beef = $this->getJson('/api/v1/items', $headers)->json('data.0.id');

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Small Vendor',
            'lines' => [
                ['inventory_item_id' => $beef, 'quantity' => 1, 'unit_cost' => 1000],
            ],
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_dept_head')
            ->assertJsonPath('data.approval_tier', 1);

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($headers, 'po-approve-'.$poId))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_finance_manager_cannot_approve_department_head_step(): void
    {
        $headers = $this->authHeaders(roles: ['finance_manager']);

        $beef = $this->getJson('/api/v1/items', $headers)->json('data.0.id');

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Large Vendor',
            'lines' => [
                ['inventory_item_id' => $beef, 'quantity' => 200, 'unit_cost' => 300],
            ],
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", [], $headers)
            ->assertJsonPath('data.approval_tier', 3);

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($headers, 'po-approve-'.$poId))
            ->assertStatus(422);
    }

    public function test_department_head_advances_tier3_po_to_finance(): void
    {
        $headers = $this->authHeaders(roles: ['department_head']);

        $beef = $this->getJson('/api/v1/items', $headers)->json('data.0.id');

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Large Vendor',
            'lines' => [
                ['inventory_item_id' => $beef, 'quantity' => 200, 'unit_cost' => 300],
            ],
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/submit", [], $headers);

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($headers, 'po-approve-'.$poId))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending_finance');
    }

    public function test_super_admin_completes_tier3_in_one_approve_call(): void
    {
        $headers = $this->authHeaders(roles: ['super_admin']);

        $beef = $this->getJson('/api/v1/items', $headers)->json('data.0.id');

        $poId = $this->postJson('/api/v1/purchase-orders', [
            'vendor_name' => 'Bulk Vendor',
            'lines' => [
                ['inventory_item_id' => $beef, 'quantity' => 200, 'unit_cost' => 300],
            ],
        ], $headers)->json('data.id');

        $this->postJson("/api/v1/purchase-orders/{$poId}/approve", [], $this->withIdempotency($headers, 'po-approve-'.$poId))
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.approval_tier', 3);
    }
}
