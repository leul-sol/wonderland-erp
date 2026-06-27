<?php

namespace Tests\Feature;

use App\Models\FiscalPeriod;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MocksS4Auth;
use Tests\TestCase;

class CatalogSmokeTest extends TestCase
{
    use MocksS4Auth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.internal_key_current' => 'test-service-key',
            'services.s1_url' => 'http://s1.test',
            'services.s2_url' => 'http://s2.test',
            'services.s3_url' => 'http://s3.test',
        ]);

        $this->seed([
            AccountSeeder::class,
            FiscalPeriodSeeder::class,
        ]);

        Http::fake([
            'http://s1.test/api/v1/*' => Http::response(['data' => []]),
            'http://s2.test/api/v1/*' => Http::response(['data' => []]),
            'http://s3.test/api/v1/*' => Http::response(['data' => []]),
        ]);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function catalogSlugs(): array
    {
        /** @var array{reports: list<array{slug: string}>} $config */
        $config = require dirname(__DIR__, 2).'/config/reports.php';
        $cases = [];
        foreach ($config['reports'] as $report) {
            $cases[$report['slug']] = [$report['slug']];
        }

        return $cases;
    }

    /**
     * @dataProvider catalogSlugs
     */
    public function test_catalog_slug_returns_json(string $slug): void
    {
        $period = $this->currentFiscalPeriod();

        $response = $this->getJson(
            '/api/v1/bi/reports/'.$slug.'?fiscal_period_id='.$period->id,
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJsonPath('data.slug', $slug);
    }

    public function test_csv_export_accepts_every_catalog_slug(): void
    {
        $period = $this->currentFiscalPeriod();

        foreach (config('reports.reports', []) as $report) {
            $slug = (string) $report['slug'];

            $response = $this->postJson('/api/v1/bi/exports', [
                'report' => $slug,
                'format' => 'csv',
                'fiscal_period_id' => $period->id,
            ], $this->authHeaders());

            $response->assertOk("CSV export failed for slug: {$slug}");
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        }
    }

    public function test_bi_report_supports_csv_export_query_param(): void
    {
        $period = $this->currentFiscalPeriod();

        $response = $this->getJson(
            '/api/v1/bi/reports/leave_summary?fiscal_period_id='.$period->id.'&export=csv',
            $this->authHeaders(),
        );

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }

    private function currentFiscalPeriod(): FiscalPeriod
    {
        $today = now()->toDateString();

        return FiscalPeriod::query()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->firstOrFail();
    }
}
