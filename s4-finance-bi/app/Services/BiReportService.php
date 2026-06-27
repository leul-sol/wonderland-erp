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
        private readonly S1IdentityClient $s1,
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
        $reservations = collect($this->s3->reservations());
        $rooms = $snapshot['rooms'];
        $totalRooms = (int) ($rooms['total'] ?? 0);
        $occupied = (int) ($rooms['occupied'] ?? 0);

        return [
            'report' => 'occupancy',
            'total_rooms' => $totalRooms,
            'occupied_rooms' => $occupied,
            'vacant_rooms' => max($totalRooms - $occupied, 0),
            'occupancy_rate' => $rooms['occupancy_rate'] ?? '0.00',
            'checked_in' => $reservations->where('status', 'checked_in')->count(),
            'checked_out_today' => $reservations->where('status', 'checked_out')->count(),
            'reservations_total' => $reservations->count(),
            'rooms' => $rooms,
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
            'by_customer_type' => $orders
                ->groupBy(fn ($o) => (string) ($o['customer_type'] ?? 'unknown'))
                ->map(fn ($group, $type) => [
                    'customer_type' => $type,
                    'order_count' => $group->count(),
                    'subtotal' => number_format($group->sum(fn ($o) => (float) ($o['subtotal'] ?? 0)), 2, '.', ''),
                    'total_amount' => number_format($group->sum(fn ($o) => (float) ($o['total_amount'] ?? $o['subtotal'] ?? 0)), 2, '.', ''),
                ])
                ->values()
                ->all(),
            'lines' => $orders->values()->all(),
        ];
    }

    public function fbSalesByCustomerType(): array
    {
        $report = $this->fbSalesReport();
        $report['report'] = 'fb_sales_by_customer_type';

        return $report;
    }

    public function hrEmployeeDirectory(): array
    {
        $employees = $this->s2->employees();

        return [
            'report' => 'hr_employee_directory',
            'total' => count($employees),
            'lines' => collect($employees)->map(fn ($employee) => [
                'id' => $employee['id'] ?? null,
                'employee_number' => $employee['employee_number'] ?? null,
                'full_name' => $employee['full_name'] ?? $employee['name'] ?? null,
                'department' => $employee['department']['name'] ?? $employee['department_name'] ?? null,
                'job_title' => $employee['job_title'] ?? null,
                'status' => $employee['status'] ?? null,
                'hire_date' => $employee['hire_date'] ?? null,
            ])->values()->all(),
        ];
    }

    public function payrollSummary(): array
    {
        $employees = collect($this->s2->employees());
        $runs = collect($this->s2->payrollRuns())->where('status', 'approved');

        return [
            'report' => 'payroll_summary',
            'active_employees' => $employees->where('status', 'active')->count(),
            'approved_runs' => $runs->count(),
            'total_gross' => number_format($runs->sum(fn ($run) => (float) ($run['total_gross'] ?? 0)), 2, '.', ''),
            'total_net' => number_format($runs->sum(fn ($run) => (float) ($run['total_net'] ?? 0)), 2, '.', ''),
            'lines' => $runs->map(fn ($run) => [
                'id' => $run['id'] ?? null,
                'period_start' => $run['period_start'] ?? null,
                'period_end' => $run['period_end'] ?? null,
                'status' => $run['status'] ?? null,
                'total_gross' => $run['total_gross'] ?? null,
                'total_net' => $run['total_net'] ?? null,
                'employee_count' => $run['employee_count'] ?? null,
            ])->values()->all(),
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
        $folios = collect($this->s3->folios())
            ->filter(fn (array $folio) => (float) ($folio['balance'] ?? $folio['outstanding_balance'] ?? 0) > 0);

        return [
            'report' => 'folio_outstanding',
            'outstanding_count' => $folios->count(),
            'total_outstanding' => number_format($folios->sum(fn (array $folio) => (float) ($folio['balance'] ?? $folio['outstanding_balance'] ?? 0)), 2, '.', ''),
            'lines' => $folios->map(fn (array $folio) => [
                'folio_id' => $folio['id'] ?? null,
                'reservation_id' => $folio['reservation_id'] ?? null,
                'status' => $folio['status'] ?? null,
                'total_charges' => $folio['total_charges'] ?? null,
                'total_payments' => $folio['total_payments'] ?? null,
                'outstanding_balance' => $folio['balance'] ?? $folio['outstanding_balance'] ?? null,
                'currency' => $folio['currency'] ?? 'ETB',
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

    public function leaveBalance(): array
    {
        $requests = collect($this->s2->leaveRequests());
        $employees = collect($this->s2->employees())->where('status', 'active');

        $lines = $employees->map(function (array $employee) use ($requests) {
            $employeeId = $employee['id'] ?? null;
            $mine = $requests->where('employee_id', $employeeId);
            $approvedDays = $mine->where('status', 'approved')->sum(fn (array $row) => (float) ($row['days_requested'] ?? 0));
            $pendingDays = $mine->where('status', 'pending')->sum(fn (array $row) => (float) ($row['days_requested'] ?? 0));

            return [
                'employee_id' => $employeeId,
                'employee_name' => (string) ($employee['full_name'] ?? $employee['name'] ?? ''),
                'department' => $employee['department']['name'] ?? null,
                'approved_days' => number_format($approvedDays, 1, '.', ''),
                'pending_days' => number_format($pendingDays, 1, '.', ''),
                'open_requests' => $mine->whereIn('status', ['pending', 'approved'])->count(),
            ];
        })->values()->all();

        return [
            'report' => 'leave_balance',
            'total_employees' => count($lines),
            'lines' => $lines,
        ];
    }

    public function inventoryExpiryAlert(): array
    {
        $alerts = $this->s3->expiryAlerts();

        return [
            'report' => 'inventory_expiry_alert',
            'alert_count' => count($alerts),
            'lines' => $alerts,
        ];
    }

    public function inventoryValuation(): array
    {
        $valuation = $this->s3->stockValuation();

        return [
            'report' => 'inventory_valuation',
            'total_value' => number_format((float) ($valuation['total_value'] ?? 0), 2, '.', ''),
            'lines' => $valuation['lines'] ?? [],
        ];
    }

    public function cashierShiftReport(): array
    {
        $shifts = $this->s3->cashierShifts();

        return [
            'report' => 'cashier_shift',
            'shift_count' => count($shifts),
            'lines' => $shifts,
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

    public function hotelDashboard(): array
    {
        $snapshot = $this->hospitalitySnapshot();

        return [
            'dashboard' => 'hotel',
            'rooms' => $snapshot['rooms'],
            'reservations' => $snapshot['reservations'],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function restaurantDashboard(): array
    {
        $fb = $this->fbSalesReport();

        return [
            'dashboard' => 'restaurant',
            'order_count' => $fb['order_count'],
            'total_revenue' => $fb['total_revenue'],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function kpiScorecard(?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $executive = $this->reports->executiveDashboard($fiscalPeriodId, $from, $to);
        $hospitality = $this->hospitalitySnapshot();
        $payroll = $this->payrollSnapshot();
        $users = collect($this->s1->users());
        $roles = collect($this->s1->roles());
        $auditLogs = collect($this->s1->auditLogs());

        return [
            'report' => 'kpi_scorecard',
            'finance' => $executive['kpis'],
            'occupancy_rate' => $hospitality['rooms']['occupancy_rate'],
            'active_employees' => $payroll['active_employees'],
            'identity' => [
                'active_users' => $users->where('is_active', true)->count(),
                'roles' => $roles->count(),
                'recent_audit_events' => $auditLogs->take(5)->values()->all(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function hrDisciplinaryHistory(): array
    {
        $employees = collect($this->s2->employees());
        $lines = [];

        foreach ($employees->take(50) as $employee) {
            $employeeId = (int) ($employee['id'] ?? 0);
            if ($employeeId === 0) {
                continue;
            }

            foreach ($this->s2->employeeDisciplinaryRecords($employeeId) as $record) {
                $lines[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee['full_name'] ?? null,
                    'action_type' => $record['action_type'] ?? null,
                    'effective_date' => $record['effective_date'] ?? null,
                    'reason' => $record['reason'] ?? null,
                    'suspension_days' => $record['suspension_days'] ?? null,
                ];
            }
        }

        return [
            'report' => 'hr_disciplinary_history',
            'record_count' => count($lines),
            'lines' => $lines,
        ];
    }

    public function hrAssetClearance(): array
    {
        $records = collect($this->s2->offboardingRecords());

        return [
            'report' => 'hr_asset_clearance',
            'record_count' => $records->count(),
            'lines' => $records->map(fn (array $record) => [
                'offboarding_id' => $record['id'] ?? null,
                'employee_id' => $record['employee_id'] ?? null,
                'employee_name' => $record['employee_name'] ?? $record['employee']['full_name'] ?? null,
                'clearance_status' => $record['clearance_status'] ?? null,
                'last_working_day' => $record['last_working_day'] ?? null,
                'severance_amount' => $record['severance_amount'] ?? null,
            ])->values()->all(),
        ];
    }

    public function payrollPension(): array
    {
        $runs = collect($this->s2->payrollRuns())->where('status', 'approved');
        $lines = $runs->flatMap(fn (array $run) => collect($run['lines'] ?? [])->map(fn (array $line) => [
            'payroll_run_id' => $run['id'] ?? null,
            'period_start' => $run['period_start'] ?? null,
            'period_end' => $run['period_end'] ?? null,
            'employee_id' => $line['employee_id'] ?? null,
            'employee_name' => $line['employee_name'] ?? null,
            'employee_pension' => $line['employee_pension'] ?? '0.00',
            'employer_pension' => $line['employer_pension'] ?? '0.00',
        ]))->values()->all();

        return [
            'report' => 'payroll_pension',
            'total_employee_pension' => number_format(collect($lines)->sum(fn (array $line) => (float) ($line['employee_pension'] ?? 0)), 2, '.', ''),
            'total_employer_pension' => number_format(collect($lines)->sum(fn (array $line) => (float) ($line['employer_pension'] ?? 0)), 2, '.', ''),
            'lines' => $lines,
        ];
    }

    public function payrollOvertime(): array
    {
        $records = $this->s2->overtimeRecords();

        return [
            'report' => 'payroll_overtime',
            'record_count' => count($records),
            'total_hours' => number_format(collect($records)->sum(fn (array $record) => (float) ($record['hours'] ?? 0)), 2, '.', ''),
            'lines' => $records,
        ];
    }

    public function leaveUtilisation(): array
    {
        $requests = collect($this->s2->leaveRequests());

        $lines = $requests->groupBy(fn (array $request) => (string) ($request['leave_type'] ?? 'unknown'))
            ->map(fn ($group, string $leaveType) => [
                'leave_type' => $leaveType,
                'request_count' => $group->count(),
                'approved_days' => number_format($group->where('status', 'approved')->sum(fn (array $row) => (float) ($row['days_requested'] ?? 0)), 1, '.', ''),
                'pending_days' => number_format($group->where('status', 'pending')->sum(fn (array $row) => (float) ($row['days_requested'] ?? 0)), 1, '.', ''),
                'rejected_count' => $group->where('status', 'rejected')->count(),
            ])
            ->values()
            ->all();

        return [
            'report' => 'leave_utilisation',
            'total_requests' => $requests->count(),
            'lines' => $lines,
        ];
    }

    public function inventoryStockMovement(): array
    {
        $items = array_slice($this->s3->items(), 0, 25);
        $lines = [];

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId === 0) {
                continue;
            }

            foreach ($this->s3->itemMovements($itemId) as $movement) {
                $lines[] = [
                    'item_id' => $itemId,
                    'item_sku' => $item['sku'] ?? null,
                    'item_name' => $item['name'] ?? null,
                    'movement_type' => $movement['movement_type'] ?? $movement['type'] ?? null,
                    'quantity' => $movement['quantity'] ?? null,
                    'reference' => $movement['reference'] ?? $movement['source_reference'] ?? null,
                    'created_at' => $movement['created_at'] ?? null,
                ];
            }
        }

        return [
            'report' => 'inventory_stock_movement',
            'movement_count' => count($lines),
            'lines' => $lines,
        ];
    }

    public function employeeConsumption(): array
    {
        $periods = $this->s3->consumptionPeriods();

        return [
            'report' => 'employee_consumption',
            'period_count' => count($periods),
            'lines' => $periods,
        ];
    }

    public function guestFolioInvoice(): array
    {
        $folios = collect($this->s3->folios())
            ->filter(fn (array $folio) => in_array($folio['status'] ?? '', ['open', 'active', 'checked_in'], true) || (float) ($folio['balance'] ?? 0) > 0)
            ->take(25);

        $lines = [];
        foreach ($folios as $folio) {
            $folioId = (int) ($folio['id'] ?? 0);
            if ($folioId === 0) {
                continue;
            }

            $invoice = $this->s3->folioInvoice($folioId);
            $lines[] = [
                'folio_id' => $folioId,
                'folio_number' => $invoice['folio_number'] ?? null,
                'guest_full_name' => $invoice['guest_full_name'] ?? null,
                'guest_phone' => $invoice['guest_phone'] ?? null,
                'guest_email' => $invoice['guest_email'] ?? null,
                'room_number' => $invoice['room_number'] ?? null,
                'check_in_date' => $invoice['check_in_date'] ?? null,
                'check_out_date' => $invoice['check_out_date'] ?? null,
                'total_charges' => $invoice['total_charges'] ?? null,
                'total_payments' => $invoice['total_payments'] ?? null,
                'outstanding_balance' => $invoice['outstanding_balance'] ?? null,
                'currency' => $invoice['currency'] ?? 'ETB',
                'issued_at' => $invoice['issued_at'] ?? null,
                'line_count' => count($invoice['lines'] ?? []),
            ];
        }

        return [
            'report' => 'guest_folio_invoice',
            'invoice_count' => count($lines),
            'lines' => $lines,
        ];
    }

    public function payrollPayslip(): array
    {
        $runs = collect($this->s2->payrollRuns())->whereIn('status', ['approved', 'locked']);

        $lines = $runs->flatMap(fn (array $run) => collect($run['lines'] ?? [])->map(fn (array $line) => [
            'employee_id' => $line['employee_id'] ?? null,
            'employee_name' => $line['employee_name'] ?? null,
            'payroll_run_id' => $run['id'] ?? null,
            'run_number' => $run['run_number'] ?? null,
            'period_start' => $run['period_start'] ?? null,
            'period_end' => $run['period_end'] ?? null,
            'gross_salary' => $line['gross_salary'] ?? null,
            'net_pay' => $line['net_pay'] ?? null,
            'pdf_route' => isset($line['employee_id'], $run['id'])
                ? '/reports/workforce/payslip/'.$line['employee_id'].'/'.$run['id']
                : null,
        ]))->values()->all();

        return [
            'report' => 'payroll_payslip',
            'payslip_count' => count($lines),
            'lines' => $lines,
        ];
    }

    public function hrGuarantorLetter(): array
    {
        $employees = collect($this->s2->employees())->take(50);
        $lines = [];

        foreach ($employees as $employee) {
            $employeeId = (int) ($employee['id'] ?? 0);
            if ($employeeId === 0) {
                continue;
            }

            foreach ($this->s2->employeeGuarantors($employeeId) as $guarantor) {
                $guarantorId = (int) ($guarantor['id'] ?? 0);
                $lines[] = [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee['full_name'] ?? null,
                    'guarantor_id' => $guarantorId,
                    'guarantor_name' => $guarantor['full_name'] ?? null,
                    'national_id' => $guarantor['national_id'] ?? null,
                    'relationship' => $guarantor['relationship'] ?? null,
                    'letter_available' => ($guarantor['letter_path'] ?? null) !== null,
                    'pdf_route' => $guarantorId > 0
                        ? '/reports/hr/guarantor-letter/'.$employeeId.'/'.$guarantorId
                        : null,
                ];
            }
        }

        return [
            'report' => 'hr_guarantor_letter',
            'letter_count' => count($lines),
            'lines' => $lines,
        ];
    }

    public function supplierPaymentHistory(): array
    {
        $payments = $this->s3->supplierPayments();

        return [
            'report' => 'supplier_payment_history',
            'payment_count' => count($payments),
            'total_paid' => number_format(collect($payments)->sum(fn (array $row) => (float) ($row['amount'] ?? 0)), 2, '.', ''),
            'lines' => $payments,
        ];
    }

    public function eventFbBilling(): array
    {
        $orders = collect($this->s3->orders())
            ->where('status', 'finalized')
            ->where('customer_type', 'event');

        return [
            'report' => 'event_fb_billing',
            'order_count' => $orders->count(),
            'total_billed' => number_format($orders->sum(fn (array $order) => (float) ($order['total_amount'] ?? $order['subtotal'] ?? 0)), 2, '.', ''),
            'lines' => $orders->map(fn (array $order) => [
                'order_id' => $order['id'] ?? null,
                'table_number' => $order['table_number'] ?? null,
                'customer_type' => $order['customer_type'] ?? null,
                'subtotal' => $order['subtotal'] ?? null,
                'total_amount' => $order['total_amount'] ?? null,
                'finalized_at' => $order['finalized_at'] ?? null,
            ])->values()->all(),
        ];
    }
}
