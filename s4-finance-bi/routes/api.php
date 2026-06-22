<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\ReceivableController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/journal-entries', [JournalController::class, 'store'])
    ->middleware('journal.post');

Route::middleware('jwt')->group(function () {
    Route::get('/accounts', [AccountController::class, 'index'])
        ->middleware('permission:S4.finance.accounts.read');

    Route::get('/fiscal-periods', [FiscalPeriodController::class, 'index'])
        ->middleware('permission:S4.finance.fiscal_periods.read');

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
});
