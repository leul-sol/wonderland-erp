<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PayrollRunController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::middleware('jwt')->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->middleware('permission:S2.workforce.employees.read');
    Route::post('/employees', [EmployeeController::class, 'store'])
        ->middleware('permission:S2.workforce.employees.create');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('permission:S2.workforce.employees.read');
    Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware('permission:S2.workforce.employees.update');
    Route::post('/employees/{employee}/archive', [EmployeeController::class, 'archive'])
        ->middleware('permission:S2.workforce.employees.archive');

    Route::get('/payroll-runs', [PayrollRunController::class, 'index'])
        ->middleware('permission:S2.workforce.payroll_runs.read');
    Route::post('/payroll-runs', [PayrollRunController::class, 'store'])
        ->middleware('permission:S2.workforce.payroll_runs.create');
    Route::get('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'show'])
        ->middleware('permission:S2.workforce.payroll_runs.read');
    Route::post('/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
        ->middleware('permission:S2.workforce.payroll_runs.approve');
});
