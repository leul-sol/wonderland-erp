<?php

namespace Tests\Feature;

use App\Support\SidebarNavBuilder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.permissions', [
            'S4.bi.dashboards.read',
            'S4.finance.reports.read',
            'S4.finance.journal_entries.read',
        ]);
    }

    public function test_finance_sidebar_has_single_dashboards_entry(): void
    {
        $navigation = app(SidebarNavBuilder::class)->build();
        $finance = collect($navigation[0]['items'] ?? [])
            ->firstWhere('key', 'finance');

        $this->assertIsArray($finance);

        $labels = array_column($finance['children'] ?? [], 'label');

        $this->assertContains('Dashboards', $labels);
        $this->assertNotContains('Executive dashboard', $labels);
        $this->assertNotContains('Hotel dashboard', $labels);
        $this->assertNotContains('Restaurant dashboard', $labels);
        $this->assertNotContains('Finance dashboard', $labels);
        $this->assertNotContains('Operations dashboard', $labels);

        $dashboardLinks = array_values(array_filter(
            $finance['children'] ?? [],
            fn (array $child): bool => str_contains((string) ($child['href'] ?? ''), '/finance/dashboard/'),
        ));

        $this->assertCount(1, $dashboardLinks);
        $this->assertSame('Dashboards', $dashboardLinks[0]['label']);
        $this->assertSame(route('finance.dashboard.executive'), $dashboardLinks[0]['href']);
    }

    public function test_legacy_finance_dashboard_children_are_stripped_when_present_in_config(): void
    {
        config()->set('portal.modules', [
            [
                'key' => 'finance',
                'label' => 'Finance',
                'route' => 'finance.reports.index',
                'permissions' => ['S4.finance.reports.read', 'S4.bi.dashboards.read'],
                'children' => [
                    ['key' => 'executive', 'label' => 'Executive dashboard', 'route' => 'finance.dashboard.executive', 'permissions' => ['S4.bi.dashboards.read']],
                    ['key' => 'hotel', 'label' => 'Hotel dashboard', 'route' => 'finance.dashboard.hotel', 'permissions' => ['S4.bi.dashboards.read']],
                    ['key' => 'reports', 'label' => 'Financial reports', 'route' => 'finance.reports.index', 'permissions' => ['S4.finance.reports.read']],
                ],
            ],
        ]);

        $this->assertTrue(Route::has('finance.dashboard.executive'));
        $this->assertTrue(Route::has('finance.dashboard.hotel'));

        $navigation = app(SidebarNavBuilder::class)->build();
        $finance = collect($navigation[0]['items'] ?? [])->firstWhere('key', 'finance');
        $labels = array_column($finance['children'] ?? [], 'label');

        $this->assertSame(['Dashboards', 'Financial reports'], $labels);
    }
}
