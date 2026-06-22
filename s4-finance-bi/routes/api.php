<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BiReportController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\OperationalEventController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RtmController;
use App\Http\Controllers\UatController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/journal-entries', [JournalController::class, 'store'])
    ->middleware('journal.post');

Route::middleware('jwt')->group(function () {
    Route::get('/accounts', [AccountController::class, 'index'])
        ->middleware('permission:S4.finance.accounts.read');

    Route::get('/accounts/{account}', [AccountController::class, 'show'])
        ->middleware('permission:S4.finance.accounts.read');
    Route::post('/accounts', [AccountController::class, 'store'])
        ->middleware('permission:S4.finance.accounts.create');
    Route::patch('/accounts/{account}', [AccountController::class, 'update'])
        ->middleware('permission:S4.finance.accounts.update');

    Route::get('/fiscal-periods', [FiscalPeriodController::class, 'index'])
        ->middleware('permission:S4.finance.fiscal_periods.read');
    Route::post('/fiscal-periods', [FiscalPeriodController::class, 'store'])
        ->middleware('permission:S4.finance.fiscal_periods.create');

    Route::post('/fiscal-periods/{fiscalPeriod}/close', [FiscalPeriodController::class, 'close'])
        ->middleware('permission:S4.finance.fiscal_periods.close');

    Route::post('/fiscal-periods/{fiscalPeriod}/lock', [FiscalPeriodController::class, 'lock'])
        ->middleware('permission:S4.finance.fiscal_periods.lock');

    Route::get('/journal-entries', [JournalController::class, 'index'])
        ->middleware('permission:S4.finance.journal_entries.read');

    Route::get('/journal-entries/{journalEntry}', [JournalController::class, 'show'])
        ->middleware('permission:S4.finance.journal_entries.read');

    Route::post('/journal-entries/{journalEntry}/approve', [JournalController::class, 'approve'])
        ->middleware('permission:S4.finance.journal_entries.approve');

    Route::post('/journal-entries/{journalEntry}/post', [JournalController::class, 'postApproved'])
        ->middleware('permission:S4.finance.journal_entries.approve');

    Route::post('/journal-entries/{journalEntry}/reverse', [JournalController::class, 'reverse'])
        ->middleware('permission:S4.finance.journal_entries.reverse');

    Route::get('/receivables', [ReceivableController::class, 'index'])
        ->middleware('permission:S4.finance.receivables.read');

    Route::post('/receivables/{receivable}/settle', [ReceivableController::class, 'settle'])
        ->middleware('permission:S4.finance.receivables.settle');

    Route::get('/payables', [PayableController::class, 'index'])
        ->middleware('permission:S4.finance.payables.read');

    Route::post('/payables/{payable}/settle', [PayableController::class, 'settle'])
        ->middleware('permission:S4.finance.payables.settle');

    Route::get('/reports/trial-balance', [ReportController::class, 'trialBalance'])
        ->middleware('permission:S4.finance.reports.read');

    Route::get('/reports/income-statement', [ReportController::class, 'incomeStatement'])
        ->middleware('permission:S4.finance.reports.read');

    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])
        ->middleware('permission:S4.finance.reports.read');

    Route::get('/reports/cash-flow', [ReportController::class, 'cashFlow'])
        ->middleware('permission:S4.finance.reports.read');

    Route::get('/dashboards/executive', [DashboardController::class, 'executive'])
        ->middleware('permission:S4.bi.dashboards.read');

    Route::get('/dashboards/operations', [DashboardController::class, 'operations'])
        ->middleware('permission:S4.bi.dashboards.read');

    Route::get('/bi/reports', [BiReportController::class, 'catalog'])
        ->middleware('permission:S4.bi.reports.read');

    Route::get('/bi/reports/revenue-by-source', [BiReportController::class, 'revenueBySource'])
        ->middleware('permission:S4.bi.reports.read');

    Route::get('/bi/reports/payroll-snapshot', [BiReportController::class, 'payrollSnapshot'])
        ->middleware('permission:S4.bi.reports.read');

    Route::get('/bi/reports/hospitality-snapshot', [BiReportController::class, 'hospitalitySnapshot'])
        ->middleware('permission:S4.bi.reports.read');

    Route::get('/bi/reports/{slug}', [BiReportController::class, 'show'])
        ->middleware('permission:S4.bi.reports.read');

    Route::post('/bi/exports', [ExportController::class, 'store'])
        ->middleware('permission:S4.bi.export.create');

    Route::get('/bi/rtm', [RtmController::class, 'index'])
        ->middleware('permission:S4.bi.rtm.read');

    Route::get('/bi/rtm/{rtmEntry}', [RtmController::class, 'show'])
        ->middleware('permission:S4.bi.rtm.read');

    Route::patch('/bi/rtm/{rtmEntry}', [RtmController::class, 'update'])
        ->middleware('permission:S4.bi.rtm.update');

    Route::get('/bi/uat', [UatController::class, 'index'])
        ->middleware('permission:S4.bi.uat.read');

    Route::get('/bi/uat/{uatScenario}', [UatController::class, 'show'])
        ->middleware('permission:S4.bi.uat.read');

    Route::post('/bi/uat/{uatScenario}/results', [UatController::class, 'recordResult'])
        ->middleware('permission:S4.bi.uat.update');

    Route::get('/bi/operational-events', [OperationalEventController::class, 'index'])
        ->middleware('permission:S4.bi.reports.read');

    Route::get('/finance/budgets', [BudgetController::class, 'index'])
        ->middleware('permission:S4.finance.budgets.read');

    Route::post('/finance/budgets', [BudgetController::class, 'store'])
        ->middleware('permission:S4.finance.budgets.create');
});
