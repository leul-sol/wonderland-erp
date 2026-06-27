<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use App\Models\ReportCacheLog;
use App\Services\BiCacheService;
use App\Support\FinanceCacheRegistry;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class Phase2AutomatedTest extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_key_current' => 'test-service-key',
            'services.s2_url' => 'http://s2.test',
            'services.s3_url' => 'http://s3.test',
        ]);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);

        FinanceCacheRegistry::reset();

        Http::fake([
            'http://s2.test/api/v1/employees' => Http::response([
                'data' => [
                    ['id' => 1, 'employee_number' => 'E001', 'full_name' => 'Abebe', 'status' => 'active', 'department' => ['name' => 'Finance']],
                ],
            ]),
            'http://s2.test/api/v1/payroll-runs' => Http::response([
                'data' => [
                    ['id' => 10, 'status' => 'approved', 'total_gross' => '10000.00', 'total_net' => '8000.00', 'employee_count' => 5],
                ],
            ]),
            'http://s3.test/api/v1/orders' => Http::response([
                'data' => [
                    ['id' => 1, 'status' => 'finalized', 'customer_type' => 'outside_cash', 'subtotal' => '100.00', 'total_amount' => '126.50'],
                    ['id' => 2, 'status' => 'finalized', 'customer_type' => 'hotel_guest', 'subtotal' => '200.00', 'total_amount' => '253.00'],
                ],
            ]),
        ]);
    }

    public function test_fiscal_period_open_command_creates_next_period(): void
    {
        $latest = FiscalPeriod::query()->orderByDesc('end_date')->firstOrFail();
        $expectedStart = $latest->end_date->copy()->addDay()->toDateString();

        Artisan::call('s4:fiscal-period:open');

        $this->assertDatabaseHas('fiscal_periods', [
            'start_date' => $expectedStart,
            'status' => 'open',
        ]);
    }

    public function test_report_cache_purge_command_invalidates_caches(): void
    {
        app(BiCacheService::class)->recordHit('finance.revenue.today', 60);

        Artisan::call('s4:report-cache:purge');

        $this->assertTrue(
            ReportCacheLog::query()->where('event', 'invalidated')->exists()
        );
    }

    public function test_hr_employee_directory_report_route_alias(): void
    {
        $this->getJson('/api/v1/reports/hr/employee-directory', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.report', 'hr_employee_directory')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.lines.0.employee_number', 'E001');
    }

    public function test_fb_sales_by_customer_type_report_includes_type_breakdown(): void
    {
        $this->getJson('/api/v1/reports/hospitality/fb-sales-by-customer-type', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.report', 'fb_sales_by_customer_type')
            ->assertJsonStructure(['data' => ['by_customer_type']]);
    }

    public function test_payroll_summary_report_route_returns_approved_run_totals(): void
    {
        $this->getJson('/api/v1/reports/workforce/payroll-summary', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.report', 'payroll_summary')
            ->assertJsonPath('data.total_gross', '10000.00')
            ->assertJsonPath('data.lines.0.total_net', '8000.00');
    }
}
