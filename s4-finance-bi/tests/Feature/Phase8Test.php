<?php

namespace Tests\Feature;

use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase8Test extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);
    }

    public function test_departmental_report_route(): void
    {
        $this->getJson('/api/v1/reports/departmental', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.report', 'departmental');
    }

    public function test_pdf_dashboard_routes_exist(): void
    {
        $this->getJson('/api/v1/dashboard/executive', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.dashboard', 'executive');

        $this->getJson('/api/v1/dashboard/finance', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.dashboard', 'finance');
    }

    public function test_report_excel_export_query_param(): void
    {
        $response = $this->get('/api/v1/reports/trial-balance?export=excel', $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.ms-excel',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_account_create_with_parent_and_sub_type(): void
    {
        $parent = $this->postJson('/api/v1/accounts', [
            'code' => '4100',
            'name' => 'Revenue Parent',
            'type' => 'income',
            'sub_type' => 'operating_income',
            'normal_balance' => 'credit',
        ], $this->authHeaders())->assertCreated()->json('data');

        $this->putJson('/api/v1/accounts/'.$parent['id'], [
            'name' => 'Revenue Parent Updated',
            'sub_type' => 'room_revenue',
        ], $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.sub_type', 'room_revenue');
    }
}
