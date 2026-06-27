<?php

namespace App\Services;

use InvalidArgumentException;

class ReportCatalogService
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly BiReportService $biReports,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(?string $category = null): array
    {
        $reports = collect(config('reports.reports', []));

        if ($category !== null && $category !== '') {
            $reports = $reports->where('category', $category);
        }

        return [
            'catalog' => 'bi_reports',
            'total' => $reports->count(),
            'reports' => $reports->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $slug, ?int $fiscalPeriodId, ?string $from, ?string $to): array
    {
        $definition = collect(config('reports.reports', []))->firstWhere('slug', $slug);

        if ($definition === null) {
            throw new InvalidArgumentException('Unknown report slug: '.$slug);
        }

        $data = match ($slug) {
            'trial_balance' => $this->reports->trialBalance($fiscalPeriodId, $from, $to),
            'income_statement' => $this->reports->incomeStatement($fiscalPeriodId, $from, $to),
            'balance_sheet' => $this->reports->balanceSheet($fiscalPeriodId, $from, $to),
            'cash_flow' => $this->reports->cashFlow($fiscalPeriodId, $from, $to),
            'gl_detail' => $this->reports->glDetail($fiscalPeriodId, $from, $to),
            'ar_aging' => $this->reports->arAging(),
            'ap_aging' => $this->reports->apAging(),
            'revenue_by_source' => $this->biReports->revenueBySource($fiscalPeriodId, $from, $to),
            'hospitality_snapshot' => $this->biReports->hospitalitySnapshot(),
            'occupancy' => $this->biReports->occupancyReport(),
            'reservation_pipeline' => $this->biReports->reservationPipeline(),
            'fb_sales' => $this->biReports->fbSalesReport(),
            'fb_sales_by_customer_type' => $this->biReports->fbSalesByCustomerType(),
            'hr_employee_directory' => $this->biReports->hrEmployeeDirectory(),
            'payroll_summary' => $this->biReports->payrollSummary(),
            'inventory_status' => $this->biReports->inventoryStatus(),
            'inventory_expiry_alert' => $this->biReports->inventoryExpiryAlert(),
            'inventory_valuation' => $this->biReports->inventoryValuation(),
            'cashier_shift' => $this->biReports->cashierShiftReport(),
            'purchase_order_status' => $this->biReports->purchaseOrderStatus(),
            'folio_outstanding' => $this->biReports->folioOutstanding(),
            'room_revenue_mix' => $this->biReports->roomRevenueMix($fiscalPeriodId, $from, $to),
            'payroll_snapshot' => $this->biReports->payrollSnapshot(),
            'headcount_by_department' => $this->biReports->headcountByDepartment(),
            'leave_summary' => $this->biReports->leaveSummary(),
            'leave_balance' => $this->biReports->leaveBalance(),
            'leave_utilisation' => $this->biReports->leaveUtilisation(),
            'payroll_pension' => $this->biReports->payrollPension(),
            'payroll_overtime' => $this->biReports->payrollOvertime(),
            'hr_disciplinary_history' => $this->biReports->hrDisciplinaryHistory(),
            'hr_asset_clearance' => $this->biReports->hrAssetClearance(),
            'inventory_stock_movement' => $this->biReports->inventoryStockMovement(),
            'employee_consumption' => $this->biReports->employeeConsumption(),
            'guest_folio_invoice' => $this->biReports->guestFolioInvoice(),
            'payroll_payslip' => $this->biReports->payrollPayslip(),
            'hr_guarantor_letter' => $this->biReports->hrGuarantorLetter(),
            'supplier_payment_history' => $this->biReports->supplierPaymentHistory(),
            'event_fb_billing' => $this->biReports->eventFbBilling(),
            'payroll_cost_trend' => $this->biReports->payrollCostTrend(),
            'executive_dashboard' => $this->reports->executiveDashboard($fiscalPeriodId, $from, $to),
            'operations_dashboard' => $this->biReports->operationsDashboard($fiscalPeriodId, $from, $to),
            'budget_variance' => $this->biReports->budgetVariance($fiscalPeriodId, $from, $to),
            'kpi_scorecard' => $this->biReports->kpiScorecard($fiscalPeriodId, $from, $to),
            default => throw new InvalidArgumentException('Report not implemented: '.$slug),
        };

        return array_merge($data, [
            'slug' => $slug,
            'name' => $definition['name'],
            'category' => $definition['category'],
        ]);
    }
}
