<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDeductionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayrollRunController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::post('/employees/{employee}/deductions', [EmployeeDeductionController::class, 'store'])
    ->middleware('service.key');

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

    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
        ->middleware('permission:S2.workforce.leave_requests.read');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])
        ->middleware('permission:S2.workforce.leave_requests.create');
    Route::get('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'show'])
        ->middleware('permission:S2.workforce.leave_requests.read');
    Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])
        ->middleware('permission:S2.workforce.leave_requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])
        ->middleware('permission:S2.workforce.leave_requests.reject');

    Route::get('/attendance-records', [AttendanceController::class, 'index'])
        ->middleware('permission:S2.workforce.attendance.read');
    Route::post('/attendance-records', [AttendanceController::class, 'store'])
        ->middleware('permission:S2.workforce.attendance.create');

    Route::get('/severance-calculations', [EmployeeDeductionController::class, 'severanceIndex'])
        ->middleware('permission:S2.workforce.severance.read');
    Route::post('/employees/{employee}/severance/calculate', [EmployeeDeductionController::class, 'calculateSeverance'])
        ->middleware('permission:S2.workforce.severance.calculate');
    Route::post('/severance-calculations/{severanceCalculation}/pay', [EmployeeDeductionController::class, 'paySeverance'])
        ->middleware('permission:S2.workforce.severance.pay');
});
