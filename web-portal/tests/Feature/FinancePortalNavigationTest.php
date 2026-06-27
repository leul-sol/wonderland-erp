<?php

namespace Tests\Feature;

use App\Services\Api\S4FinanceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class FinancePortalNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Session::put('portal.access_token', 'test-token');
        Session::put('portal.permissions', [
            'S4.finance.reports.read',
            'S4.finance.journal_entries.read',
            'S4.finance.journal_entries.create',
            'S4.finance.journal_entries.approve',
            'S4.finance.journal_entries.reverse',
            'S4.finance.receivables.read',
            'S4.finance.receivables.settle',
            'S4.finance.payables.read',
            'S4.finance.payables.settle',
            'S4.finance.budgets.read',
            'S4.finance.fiscal_periods.read',
            'S4.finance.fiscal_periods.create',
            'S4.finance.accounts.read',
            'S4.finance.accounts.create',
            'S4.finance.accounts.update',
            'S4.finance.budgets.create',
            'S4.bi.dashboards.read',
            'S4.bi.reports.read',
            'S4.bi.export.create',
            'S4.bi.rtm.read',
            'S4.bi.rtm.update',
            'S4.bi.uat.read',
            'S4.bi.uat.update',
        ]);
    }

    public function test_all_finance_portal_pages_render(): void
    {
        $empty = ['data' => []];
        $dashboard = ['data' => ['kpis' => [], 'generated_at' => now()->toIso8601String()]];

        $this->mock(S4FinanceClient::class, function (MockInterface $mock) use ($empty, $dashboard): void {
            $mock->shouldReceive('fetchMany')->andReturn([]);
            $mock->shouldReceive('trialBalance')->andReturn($empty);
            $mock->shouldReceive('incomeStatement')->andReturn($empty);
            $mock->shouldReceive('balanceSheet')->andReturn($empty);
            $mock->shouldReceive('cashFlow')->andReturn($empty);
            $mock->shouldReceive('departmental')->andReturn($empty);
            $mock->shouldReceive('journalEntries')->andReturn($empty);
            $mock->shouldReceive('accounts')->andReturn($empty);
            $mock->shouldReceive('fiscalPeriods')->andReturn($empty);
            $mock->shouldReceive('receivables')->andReturn($empty);
            $mock->shouldReceive('payables')->andReturn($empty);
            $mock->shouldReceive('budgetVariance')->andReturn($empty);
            $mock->shouldReceive('budgetLines')->andReturn($empty);
            $mock->shouldReceive('createBudgetLine')->andReturn($empty);
            $mock->shouldReceive('createFiscalPeriod')->andReturn(['data' => ['id' => 99]]);
            $mock->shouldReceive('executiveDashboard')->andReturn($dashboard);
            $mock->shouldReceive('operationsDashboard')->andReturn($dashboard);
            $mock->shouldReceive('hotelDashboard')->andReturn(['data' => ['dashboard' => 'hotel', 'rooms' => []]]);
            $mock->shouldReceive('restaurantDashboard')->andReturn(['data' => ['dashboard' => 'restaurant']]);
            $mock->shouldReceive('financeDashboard')->andReturn($dashboard);
            $mock->shouldReceive('biReportCatalog')->andReturn(['data' => ['reports' => [['slug' => 'trial_balance', 'name' => 'Trial Balance', 'category' => 'finance', 'module' => 'report']]]]);
            $mock->shouldReceive('biReport')->andReturn(['data' => ['report' => 'trial_balance', 'lines' => []]]);
            $mock->shouldReceive('rtmEntries')->andReturn(['data' => [], 'meta' => []]);
            $mock->shouldReceive('uatScenarios')->andReturn(['data' => [], 'meta' => []]);
        });

        $pages = [
            ['/finance/reports', 'Finance/Reports/Index'],
            ['/finance/journals', 'Finance/Journals/Index'],
            ['/finance/fiscal-periods', 'Finance/FiscalPeriods/Index'],
            ['/finance/receivables', 'Finance/Receivables/Index'],
            ['/finance/payables', 'Finance/Payables/Index'],
            ['/finance/budget', 'Finance/Budget/Index'],
            ['/finance/accounts', 'Finance/Accounts/Index'],
            ['/finance/dashboard/executive', 'Finance/Dashboard/Index'],
            ['/finance/dashboard/hotel', 'Finance/Dashboard/Index'],
            ['/finance/dashboard/restaurant', 'Finance/Dashboard/Index'],
            ['/finance/dashboard/finance', 'Finance/Dashboard/Index'],
            ['/finance/dashboard/operations', 'Finance/Dashboard/Index'],
            ['/finance/bi-reports', 'Finance/BiReports/Index'],
            ['/finance/bi-reports/trial_balance', 'Finance/BiReports/Show'],
            ['/finance/rtm', 'Finance/Rtm/Index'],
            ['/finance/uat', 'Finance/Uat/Index'],
        ];

        foreach ($pages as [$path, $component]) {
            $response = $this->get($path);
            $response->assertOk();
            $response->assertInertia(fn ($page) => $page->component($component, false));
        }
    }
}
