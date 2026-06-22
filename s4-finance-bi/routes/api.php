<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\JournalController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/journal-entries', [JournalController::class, 'store'])
    ->middleware('journal.post');

Route::middleware('jwt')->group(function () {
    Route::get('/accounts', [AccountController::class, 'index'])
        ->middleware('permission:S4.finance.accounts.read');

    Route::get('/fiscal-periods', [FiscalPeriodController::class, 'index'])
        ->middleware('permission:S4.finance.fiscal_periods.read');

    Route::get('/journal-entries', [JournalController::class, 'index'])
        ->middleware('permission:S4.finance.journal_entries.read');

    Route::get('/journal-entries/{journalEntry}', [JournalController::class, 'show'])
        ->middleware('permission:S4.finance.journal_entries.read');
});
