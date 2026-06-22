<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class BiReportService
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly S2WorkforceClient $s2,
        private readonly S3HospitalityClient $s3,
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
}
