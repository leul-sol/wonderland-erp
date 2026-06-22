<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class BiReportService
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly S2WorkforceClient $s2,
        private readonly S3HospitalityClient $s3,
        private readonly BudgetService $budgets,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function revenueBySource(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->reports->resolveDateRange($fiscalPeriodId, $from, $to);

        $rows = JournalEntry::query()
            ->where('status', 'posted')
            ->whereDate('entry_date', '>=', $range['from'])
            ->whereDate('entry_date', '<=', $range['to'])
            ->select([
                'source_module',
                DB::raw('COUNT(*) as entry_count'),
                DB::raw('SUM(total_credit) as credit_total'),
            ])
            ->groupBy('source_module')
            ->orderBy('source_module')
            ->get();

        $lines = $rows->map(fn ($row) => [
            'source_module' => $row->source_module,
            'entry_count' => (int) $row->entry_count,
            'volume' => number_format((float) $row->credit_total, 2, '.', ''),
        ])->values()->all();

        return [
            'report' => 'revenue_by_source',
            'from' => $range['from'],
            'to' => $range['to'],
            'lines' => $lines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payrollSnapshot(): array
    {
        $employees = $this->s2->employees();
        $runs = $this->s2->payrollRuns();

        $activeEmployees = collect($employees)->where('status', 'active')->count();
        $approvedRuns = collect($runs)->where('status', 'approved');
        $latestRun = collect($runs)->sortByDesc('id')->first();

        return [
            'report' => 'payroll_snapshot',
            'active_employees' => $activeEmployees,
            'approved_payroll_runs' => $approvedRuns->count(),
            'latest_payroll_run' => $latestRun,
            'payroll_runs' => array_values($runs),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hospitalitySnapshot(): array
    {
        $rooms = $this->s3->rooms();
        $reservations = $this->s3->reservations();
        $orders = $this->s3->orders();

        $roomCollection = collect($rooms);
        $reservationCollection = collect($reservations);
        $orderCollection = collect($orders);

        $occupied = $roomCollection->where('status', 'occupied')->count();
        $totalRooms = $roomCollection->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupied / $totalRooms) * 100, 2) : 0.0;

        $fbRevenue = $orderCollection
            ->where('status', 'finalized')
            ->sum(fn ($order) => (float) ($order['subtotal'] ?? 0));

        return [
            'report' => 'hospitality_snapshot',
            'rooms' => [
                'total' => $totalRooms,
                'occupied' => $occupied,
                'occupancy_rate' => number_format($occupancyRate, 2, '.', ''),
            ],
            'reservations' => [
                'total' => $reservationCollection->count(),
                'checked_in' => $reservationCollection->where('status', 'checked_in')->count(),
                'checked_out' => $reservationCollection->where('status', 'checked_out')->count(),
            ],
            'fb_orders' => [
                'finalized_count' => $orderCollection->where('status', 'finalized')->count(),
                'revenue' => number_format($fbRevenue, 2, '.', ''),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function operationsDashboard(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->reports->resolveDateRange($fiscalPeriodId, $from, $to);
        $income = $this->reports->incomeStatement($fiscalPeriodId, $range['from'], $range['to']);
        $hospitality = $this->hospitalitySnapshot();
        $payroll = $this->payrollSnapshot();
        $revenueBySource = $this->revenueBySource($fiscalPeriodId, $range['from'], $range['to']);

        return [
            'dashboard' => 'operations',
            'from' => $range['from'],
            'to' => $range['to'],
            'finance' => [
                'revenue' => $income['revenue']['total'],
                'expenses' => $income['expenses']['total'],
                'net_income' => $income['net_income'],
            ],
            'hospitality' => $hospitality['rooms'],
            'workforce' => [
                'active_employees' => $payroll['active_employees'],
                'approved_payroll_runs' => $payroll['approved_payroll_runs'],
            ],
            'revenue_by_source' => $revenueBySource['lines'],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function occupancyReport(): array
    {
        $snapshot = $this->hospitalitySnapshot();

        return [
            'report' => 'occupancy',
            'rooms' => $snapshot['rooms'],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function reservationPipeline(): array
    {
        $reservations = $this->s3->reservations();

        return [
            'report' => 'reservation_pipeline',
            'lines' => collect($reservations)->map(fn ($r) => [
                'id' => $r['id'] ?? null,
                'guest_name' => $r['guest_name'] ?? null,
                'status' => $r['status'] ?? null,
                'check_in_date' => $r['check_in_date'] ?? null,
                'check_out_date' => $r['check_out_date'] ?? null,
            ])->values()->all(),
        ];
    }

    public function fbSalesReport(): array
    {
        $orders = collect($this->s3->orders())->where('status', 'finalized');

        return [
            'report' => 'fb_sales',
            'order_count' => $orders->count(),
            'total_revenue' => number_format($orders->sum(fn ($o) => (float) ($o['subtotal'] ?? 0)), 2, '.', ''),
            'lines' => $orders->values()->all(),
        ];
    }

    public function inventoryStatus(): array
    {
        $items = $this->s3->items();

        return [
            'report' => 'inventory_status',
            'item_count' => count($items),
            'lines' => $items,
        ];
    }

    public function purchaseOrderStatus(): array
    {
        $orders = $this->s3->purchaseOrders();

        return [
            'report' => 'purchase_order_status',
            'lines' => $orders,
            'by_status' => collect($orders)->groupBy('status')->map->count()->all(),
        ];
    }

    public function folioOutstanding(): array
    {
        $reservations = collect($this->s3->reservations())->where('status', 'checked_in');

        return [
            'report' => 'folio_outstanding',
            'checked_in_guests' => $reservations->count(),
            'lines' => $reservations->map(fn ($r) => [
                'reservation_id' => $r['id'] ?? null,
                'guest_name' => $r['guest_name'] ?? null,
                'folio_id' => $r['folio_id'] ?? null,
            ])->values()->all(),
        ];
    }

    public function roomRevenueMix(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $range = $this->reports->resolveDateRange($fiscalPeriodId, $from, $to);

        $amount = JournalLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where('accounts.code', '4001')
            ->whereDate('journal_entries.entry_date', '>=', $range['from'])
            ->whereDate('journal_entries.entry_date', '<=', $range['to'])
            ->sum('journal_lines.credit');

        return [
            'report' => 'room_revenue_mix',
            'from' => $range['from'],
            'to' => $range['to'],
            'room_revenue' => number_format((float) $amount, 2, '.', ''),
        ];
    }

    public function headcountByDepartment(): array
    {
        $employees = collect($this->s2->employees())->where('status', 'active');

        return [
            'report' => 'headcount_by_department',
            'total' => $employees->count(),
            'lines' => $employees->groupBy(fn ($e) => $e['department']['name'] ?? 'Unassigned')
                ->map(fn ($group, $dept) => ['department' => $dept, 'count' => $group->count()])
                ->values()->all(),
        ];
    }

    public function leaveSummary(): array
    {
        $requests = $this->s2->leaveRequests();

        return [
            'report' => 'leave_summary',
            'total_requests' => count($requests),
            'by_status' => collect($requests)->groupBy('status')->map->count()->all(),
            'lines' => $requests,
        ];
    }

    public function payrollCostTrend(): array
    {
        $runs = collect($this->s2->payrollRuns())->where('status', 'approved');

        return [
            'report' => 'payroll_cost_trend',
            'lines' => $runs->map(fn ($run) => [
                'id' => $run['id'] ?? null,
                'period_start' => $run['period_start'] ?? null,
                'period_end' => $run['period_end'] ?? null,
                'total_gross' => $run['total_gross'] ?? null,
                'total_net' => $run['total_net'] ?? null,
            ])->values()->all(),
        ];
    }

    public function budgetVariance(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $income = $this->reports->incomeStatement($fiscalPeriodId, $from, $to);
        $actual = (float) $income['net_income'];
        $range = $this->reports->resolveDateRange($fiscalPeriodId, $from, $to);
        $periodId = $range['fiscal_period']?->id;

        $budgetNet = $periodId !== null ? $this->budgets->budgetNetIncome((int) $periodId) : 0.0;

        $variance = round($actual - $budgetNet, 2);

        return [
            'report' => 'budget_variance',
            'fiscal_period_id' => $periodId,
            'actual_net_income' => $income['net_income'],
            'budget_net_income' => number_format($budgetNet, 2, '.', ''),
            'variance' => number_format($variance, 2, '.', ''),
        ];
    }

    public function kpiScorecard(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $executive = $this->reports->executiveDashboard($fiscalPeriodId, $from, $to);
        $hospitality = $this->hospitalitySnapshot();
        $payroll = $this->payrollSnapshot();

        return [
            'report' => 'kpi_scorecard',
            'finance' => $executive['kpis'],
            'occupancy_rate' => $hospitality['rooms']['occupancy_rate'],
            'active_employees' => $payroll['active_employees'],
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
