<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApiErrors;
use App\Http\Controllers\Concerns\RespondsWithReportExport;
use App\Services\BiReportService;
use App\Services\ExportService;
use App\Services\ReportCatalogService;
use App\Services\S2WorkforceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiReportController extends Controller
{
    use RespondsWithApiErrors;
    use RespondsWithReportExport;

    public function __construct(
        private readonly BiReportService $biReports,
        private readonly ReportCatalogService $catalog,
        private readonly ExportService $exports,
        private readonly S2WorkforceClient $s2,
    ) {
    }

    public function catalog(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->catalog->catalog($request->input('category'))]);
    }

    public function show(Request $request, string $slug): JsonResponse|StreamedResponse|Response
    {
        try {
            $periodId = $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null;
            $from = $request->input('from');
            $to = $request->input('to');
            $data = $this->catalog->run($slug, $periodId, $from, $to);
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return $this->respondWithReport($request, $this->exports, $slug, $data, $periodId, $from, $to);
    }

    public function payslipPdf(int $employeeId, int $payrollRunId): Response|JsonResponse
    {
        try {
            $binary = $this->s2->payslipPdf($employeeId, $payrollRunId);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="payslip-'.$employeeId.'-'.$payrollRunId.'.pdf"',
        ]);
    }

    public function guarantorLetterPdf(int $employeeId, int $guarantorId): Response|JsonResponse
    {
        try {
            $binary = $this->s2->guarantorLetterPdf($employeeId, $guarantorId);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="guarantor-letter-'.$employeeId.'-'.$guarantorId.'.pdf"',
        ]);
    }

    public function employeeDirectory(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'hr_employee_directory');
    }

    public function fbSalesByCustomerType(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'fb_sales_by_customer_type');
    }

    public function payrollSummary(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'payroll_summary');
    }

    public function leaveBalance(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'leave_balance');
    }

    public function inventoryExpiryAlert(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'inventory_expiry_alert');
    }

    public function inventoryValuation(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'inventory_valuation');
    }

    public function cashierShift(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'cashier_shift');
    }

    public function payrollOvertime(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'payroll_overtime');
    }

    public function payrollPension(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'payroll_pension');
    }

    public function payrollPayslip(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'payroll_payslip');
    }

    public function leaveUtilisation(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'leave_utilisation');
    }

    public function disciplinaryHistory(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'hr_disciplinary_history');
    }

    public function assetClearance(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'hr_asset_clearance');
    }

    public function guarantorLetter(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'hr_guarantor_letter');
    }

    public function stockMovement(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'inventory_stock_movement');
    }

    public function employeeConsumption(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'employee_consumption');
    }

    public function guestFolioInvoice(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'guest_folio_invoice');
    }

    public function folioOutstanding(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'folio_outstanding');
    }

    public function supplierPaymentHistory(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'supplier_payment_history');
    }

    public function eventFbBilling(Request $request): JsonResponse|StreamedResponse|Response
    {
        return $this->show($request, 'event_fb_billing');
    }

    public function revenueBySource(Request $request): JsonResponse
    {
        try {
            $data = $this->biReports->revenueBySource(
                $request->filled('fiscal_period_id') ? (int) $request->input('fiscal_period_id') : null,
                $request->input('from'),
                $request->input('to'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }

    public function payrollSnapshot(): JsonResponse
    {
        try {
            $data = $this->biReports->payrollSnapshot();
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }

    public function hospitalitySnapshot(): JsonResponse
    {
        try {
            $data = $this->biReports->hospitalitySnapshot();
        } catch (\RuntimeException $e) {
            return $this->error('INTEGRATION_ERROR', $e->getMessage(), 502);
        }

        return response()->json(['data' => $data]);
    }
}
