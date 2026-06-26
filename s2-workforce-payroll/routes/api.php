<?php

use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DisciplinaryRecordController;
use App\Http\Controllers\EmployeeAssetController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDeductionController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OffboardingController;
use App\Http\Controllers\OvertimeRateController;
use App\Http\Controllers\OvertimeRecordController;
use App\Http\Controllers\PayrollRunController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\PositionController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/internal/employees/{employee}', [EmployeeController::class, 'show'])
    ->middleware('service.key');

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

    Route::get('/departments', [DepartmentController::class, 'index'])
        ->middleware('permission:S2.hr.departments.read');
    Route::post('/departments', [DepartmentController::class, 'store'])
        ->middleware('permission:S2.hr.departments.write');
    Route::get('/departments/{department}', [DepartmentController::class, 'show'])
        ->middleware('permission:S2.hr.departments.read');
    Route::patch('/departments/{department}', [DepartmentController::class, 'update'])
        ->middleware('permission:S2.hr.departments.write');
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('permission:S2.hr.departments.write');

    Route::get('/positions', [PositionController::class, 'index'])
        ->middleware('permission:S2.workforce.positions.read');
    Route::post('/positions', [PositionController::class, 'store'])
        ->middleware('permission:S2.workforce.positions.create');
    Route::get('/positions/{position}', [PositionController::class, 'show'])
        ->middleware('permission:S2.workforce.positions.read');
    Route::patch('/positions/{position}', [PositionController::class, 'update'])
        ->middleware('permission:S2.workforce.positions.update');
    Route::delete('/positions/{position}', [PositionController::class, 'destroy'])
        ->middleware('permission:S2.workforce.positions.delete');

    Route::get('/employees/{employee}/disciplinary-records', [DisciplinaryRecordController::class, 'index'])
        ->middleware('permission:S2.hr.disciplinary.read');
    Route::post('/employees/{employee}/disciplinary-records', [DisciplinaryRecordController::class, 'store'])
        ->middleware('permission:S2.hr.disciplinary.write');

    Route::get('/asset-types', [AssetTypeController::class, 'index'])
        ->middleware('permission:S2.hr.assets.read');
    Route::post('/asset-types', [AssetTypeController::class, 'store'])
        ->middleware('permission:S2.hr.assets.write');
    Route::get('/asset-types/{assetType}', [AssetTypeController::class, 'show'])
        ->middleware('permission:S2.hr.assets.read');
    Route::patch('/asset-types/{assetType}', [AssetTypeController::class, 'update'])
        ->middleware('permission:S2.hr.assets.write');
    Route::delete('/asset-types/{assetType}', [AssetTypeController::class, 'destroy'])
        ->middleware('permission:S2.hr.assets.write');

    Route::get('/employees/{employee}/assets', [EmployeeAssetController::class, 'index'])
        ->middleware('permission:S2.hr.assets.read');
    Route::post('/employees/{employee}/assets', [EmployeeAssetController::class, 'store'])
        ->middleware('permission:S2.hr.assets.write');
    Route::put('/assets/{employeeAsset}/return', [EmployeeAssetController::class, 'returnAsset'])
        ->middleware('permission:S2.hr.assets.write');

    Route::get('/employees/{employee}/guarantors', [GuarantorController::class, 'index'])
        ->middleware('permission:S2.hr.guarantors.read');
    Route::post('/employees/{employee}/guarantors', [GuarantorController::class, 'store'])
        ->middleware('permission:S2.hr.guarantors.write');
    Route::get('/employees/{employee}/guarantors/{guarantor}/letter', [GuarantorController::class, 'downloadLetter'])
        ->middleware('permission:S2.hr.guarantors.read');

    Route::get('/offboarding-records', [OffboardingController::class, 'index'])
        ->middleware('permission:S2.workforce.offboarding.read');
    Route::get('/offboarding-records/{offboardingRecord}', [OffboardingController::class, 'show'])
        ->middleware('permission:S2.workforce.offboarding.read');
    Route::post('/employees/{employee}/offboarding', [OffboardingController::class, 'store'])
        ->middleware('permission:S2.workforce.offboarding.create');
    Route::patch('/offboarding-records/{offboardingRecord}', [OffboardingController::class, 'update'])
        ->middleware('permission:S2.workforce.offboarding.update');

    Route::get('/payroll-runs', [PayrollRunController::class, 'index'])
        ->middleware('permission:S2.workforce.payroll_runs.read');
    Route::post('/payroll-runs', [PayrollRunController::class, 'store'])
        ->middleware('permission:S2.workforce.payroll_runs.create');
    Route::get('/payroll-runs/{payrollRun}', [PayrollRunController::class, 'show'])
        ->middleware('permission:S2.workforce.payroll_runs.read');
    Route::post('/payroll-runs/{payrollRun}/submit', [PayrollRunController::class, 'submit'])
        ->middleware('permission:S2.workforce.payroll_runs.create');
    Route::post('/payroll-runs/{payrollRun}/approve', [PayrollRunController::class, 'approve'])
        ->middleware('permission:S2.workforce.payroll_runs.approve');
    Route::post('/payroll-runs/{payrollRun}/lock', [PayrollRunController::class, 'lock'])
        ->middleware('permission:S2.workforce.payroll_runs.approve');
    Route::get('/employees/{employee}/payslip/{payrollRun}', [PayslipController::class, 'show'])
        ->middleware('permission:S2.payroll.payslips.read');

    Route::get('/employees/{employee}/loans', [LoanController::class, 'index'])
        ->middleware('permission:S2.workforce.loans.read');
    Route::post('/employees/{employee}/loans', [LoanController::class, 'store'])
        ->middleware('permission:S2.workforce.loans.create');

    Route::get('/leave-types', [LeaveTypeController::class, 'index'])
        ->middleware('permission:S2.workforce.leave_types.read');
    Route::get('/leave-types/{leaveType}', [LeaveTypeController::class, 'show'])
        ->middleware('permission:S2.workforce.leave_types.read');
    Route::get('/employees/{employee}/leave-balances', [LeaveBalanceController::class, 'index'])
        ->middleware('permission:S2.workforce.leave_balances.read');

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
    Route::post('/leave-requests/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])
        ->middleware('permission:S2.workforce.leave_requests.create');

    Route::get('/overtime-rates', [OvertimeRateController::class, 'index'])
        ->middleware('permission:S2.workforce.overtime.read');
    Route::patch('/overtime-rates/{overtimeRate}', [OvertimeRateController::class, 'update'])
        ->middleware('permission:S2.workforce.overtime.update');
    Route::get('/employees/{employee}/overtime-records', [OvertimeRecordController::class, 'index'])
        ->middleware('permission:S2.workforce.overtime.read');
    Route::post('/employees/{employee}/overtime-records', [OvertimeRecordController::class, 'store'])
        ->middleware('permission:S2.workforce.overtime.create');
    Route::get('/overtime-records', [OvertimeRecordController::class, 'list'])
        ->middleware('permission:S2.workforce.overtime.read');
    Route::post('/overtime-records/{overtimeRecord}/approve', [OvertimeRecordController::class, 'approve'])
        ->middleware('permission:S2.workforce.overtime.approve');

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
