<?php

namespace Tests\Feature;

use App\Services\Api\S4FinanceClient;
use Illuminate\Support\Facades\Session;
use Mockery\MockInterface;
use Tests\TestCase;

class FinancePagesTest extends TestCase
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
            'S4.finance.fiscal_periods.read',
            'S4.finance.fiscal_periods.close',
            'S4.finance.fiscal_periods.lock',
            'S4.finance.receivables.read',
            'S4.finance.receivables.settle',
            'S4.finance.payables.read',
            'S4.finance.budgets.read',
            'S4.bi.reports.read',
            'S4.bi.dashboards.read',
            'S4.bi.export.create',
        ]);
    }

    public function test_reports_page_renders_trial_balance(): void
    {
        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('trialBalance')->once()->andReturn([
                'data' => [
                    'report' => 'trial_balance',
                    'from' => '2026-06-01',
                    'to' => '2026-06-30',
                    'lines' => [[
                        'account_code' => '1001',
                        'account_name' => 'Cash',
                        'debit_balance' => '1000.00',
                        'credit_balance' => '0.00',
                    ]],
                    'totals' => ['debit' => '1000.00', 'credit' => '1000.00'],
                ],
            ]);
        });

        $response = $this->get('/finance/reports');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Reports/Index')
            ->where('reportType', 'trial_balance')
        );
        $this->assertDeferredInertia($response, fn ($page) => $page->has('report.lines', 1));
    }

    public function test_journal_show_includes_approval_flags(): void
    {
        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('journalEntry')->once()->with(7)->andReturn([
                'data' => [
                    'id' => 7,
                    'entry_number' => 'JE-00007',
                    'entry_date' => '2026-06-01',
                    'description' => 'Manual adjustment',
                    'status' => 'draft',
                    'total_debit' => '1000.00',
                    'total_credit' => '1000.00',
                    'lines' => [],
                ],
            ]);
        });

        $response = $this->get('/finance/journals/7');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Journals/Show')
            ->where('canApproveFinance', true)
            ->where('canApproveGm', false)
        );
    }

    public function test_fiscal_periods_page_renders(): void
    {
        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('fiscalPeriods')->once()->andReturn([
                'data' => [[
                    'id' => 1,
                    'year' => 2026,
                    'period_number' => 6,
                    'start_date' => '2026-06-01',
                    'end_date' => '2026-06-30',
                    'status' => 'open',
                ]],
            ]);
        });

        $response = $this->get('/finance/fiscal-periods');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/FiscalPeriods/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page->has('fiscalPeriods', 1));
    }

    public function test_executive_dashboard_renders_kpis(): void
    {
        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('executiveDashboard')->once()->andReturn([
                'data' => [
                    'dashboard' => 'executive',
                    'from' => '2026-06-01',
                    'to' => '2026-06-30',
                    'kpis' => [
                        'revenue' => '50000.00',
                        'net_income' => '12000.00',
                    ],
                ],
            ]);
        });

        $response = $this->get('/finance/dashboard/executive');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Dashboard/Executive')
            ->has('dashboard.kpis')
        );
    }

    public function test_budget_variance_page_renders(): void
    {
        $this->mock(S4FinanceClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('budgetVariance')->once()->andReturn([
                'data' => [
                    'report' => 'budget_variance',
                    'actual_net_income' => '10000.00',
                    'budget_net_income' => '12000.00',
                    'variance' => '-2000.00',
                ],
            ]);
            $mock->shouldReceive('fiscalPeriods')->once()->andReturn(['data' => []]);
        });

        $response = $this->get('/finance/budget');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Finance/Budget/Index'));
        $this->assertDeferredInertia($response, fn ($page) => $page
            ->has('variance')
            ->has('fiscalPeriods')
        );
    }
}
