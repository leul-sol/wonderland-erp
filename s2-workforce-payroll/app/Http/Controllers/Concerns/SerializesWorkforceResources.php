<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AssetType;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\DisciplinaryRecord;
use App\Models\Employee;
use App\Models\EmployeeAsset;
use App\Models\EmployeeDeduction;
use App\Models\Guarantor;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LoanRecord;
use App\Models\OffboardingRecord;
use App\Models\OvertimeRate;
use App\Models\OvertimeRecord;
use App\Models\PayrollRun;
use App\Models\Position;
use App\Models\SeveranceCalculation;

trait SerializesWorkforceResources
{
    protected function employeePayload(Employee $employee): array
    {
        $employee->loadMissing(['department', 'position']);

        return [
            'id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'department_id' => $employee->department_id,
            'department' => $employee->department ? [
                'id' => $employee->department->id,
                'code' => $employee->department->code,
                'name' => $employee->department->name,
            ] : null,
            'position_id' => $employee->position_id,
            'position' => $employee->position ? $this->positionPayload($employee->position) : null,
            'job_title' => $employee->job_title,
            'base_salary' => (string) $employee->base_salary,
            'pension_category' => $employee->pension_category ?? 'covered',
            'default_role' => $employee->default_role,
            'status' => $employee->status,
            'hire_date' => $employee->hire_date?->toDateString(),
            'archived_at' => $employee->archived_at?->toIso8601String(),
        ];
    }

    protected function payrollRunPayload(PayrollRun $run): array
    {
        $run->loadMissing(['lines.employee']);

        return [
            'id' => $run->id,
            'run_number' => $run->run_number,
            'period_start' => $run->period_start?->toDateString(),
            'period_end' => $run->period_end?->toDateString(),
            'status' => $run->status,
            'total_gross' => (string) $run->total_gross,
            'total_net' => (string) $run->total_net,
            's4_journal_entry_id' => $run->s4_journal_entry_id,
            'approved_at' => $run->approved_at?->toIso8601String(),
            'lines' => $run->lines->map(fn ($line) => [
                'id' => $line->id,
                'employee_id' => $line->employee_id,
                'employee_name' => $line->employee?->full_name,
                'gross_salary' => (string) $line->gross_salary,
                'overtime_pay' => (string) ($line->overtime_pay ?? '0.00'),
                'loan_repayment' => (string) ($line->loan_repayment ?? '0.00'),
                'employee_pension' => (string) $line->employee_pension,
                'employer_pension' => (string) $line->employer_pension,
                'income_tax' => (string) $line->income_tax,
                'other_deductions' => (string) ($line->other_deductions ?? '0.00'),
                'net_pay' => (string) $line->net_pay,
            ])->values()->all(),
        ];
    }

    protected function leaveRequestPayload(LeaveRequest $request): array
    {
        $request->loadMissing('employee.department');

        return [
            'id' => $request->id,
            'request_number' => $request->request_number,
            'employee_id' => $request->employee_id,
            'employee' => $request->employee ? [
                'id' => $request->employee->id,
                'employee_number' => $request->employee->employee_number,
                'full_name' => $request->employee->full_name,
                'department' => $request->employee->department ? [
                    'id' => $request->employee->department->id,
                    'name' => $request->employee->department->name,
                ] : null,
            ] : null,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date?->toDateString(),
            'end_date' => $request->end_date?->toDateString(),
            'days_requested' => $request->days_requested,
            'reason' => $request->reason,
            'status' => $request->status,
            'approved_at' => $request->approved_at?->toIso8601String(),
            'approved_by' => $request->approved_by,
            'rejection_reason' => $request->rejection_reason,
        ];
    }

    protected function attendancePayload(AttendanceRecord $record): array
    {
        $record->loadMissing('employee.department');

        return [
            'id' => $record->id,
            'employee_id' => $record->employee_id,
            'employee_name' => $record->employee?->full_name,
            'work_date' => $record->work_date?->toDateString(),
            'check_in' => $record->check_in,
            'check_out' => $record->check_out,
            'hours_worked' => (string) $record->hours_worked,
            'status' => $record->status,
            'notes' => $record->notes,
        ];
    }

    protected function deductionPayload(EmployeeDeduction $deduction): array
    {
        return [
            'id' => $deduction->id,
            'employee_id' => $deduction->employee_id,
            'deduction_type' => $deduction->deduction_type,
            'amount' => (string) $deduction->amount,
            'description' => $deduction->description,
            'source_reference' => $deduction->source_reference,
            'status' => $deduction->status,
        ];
    }

    protected function severancePayload(SeveranceCalculation $calculation): array
    {
        $calculation->loadMissing('employee');

        return [
            'id' => $calculation->id,
            'employee_id' => $calculation->employee_id,
            'employee_name' => $calculation->employee?->full_name,
            'amount' => (string) $calculation->amount,
            'months_of_service' => $calculation->months_of_service,
            'calculation_date' => $calculation->calculation_date?->toDateString(),
            'status' => $calculation->status,
            's4_journal_entry_id' => $calculation->s4_journal_entry_id,
            's4_payout_journal_entry_id' => $calculation->s4_payout_journal_entry_id,
            'paid_at' => $calculation->paid_at?->toIso8601String(),
        ];
    }

    protected function positionPayload(Position $position): array
    {
        $position->loadMissing('department');

        return [
            'id' => $position->id,
            'title' => $position->title,
            'department_id' => $position->department_id,
            'department' => $position->department ? [
                'id' => $position->department->id,
                'name' => $position->department->name,
            ] : null,
            'grade' => $position->grade,
            'transport_allowance' => (string) $position->transport_allowance,
            'housing_allowance' => (string) $position->housing_allowance,
        ];
    }

    protected function offboardingPayload(OffboardingRecord $record): array
    {
        $record->loadMissing('employee');

        return [
            'id' => $record->id,
            'employee_id' => $record->employee_id,
            'employee_name' => $record->employee?->full_name,
            'initiated_date' => $record->initiated_date?->toDateString(),
            'reason' => $record->reason,
            'last_working_day' => $record->last_working_day?->toDateString(),
            'clearance_status' => $record->clearance_status,
            'severance_amount' => $record->severance_amount !== null ? (string) $record->severance_amount : null,
            'notes' => $record->notes,
        ];
    }

    protected function leaveTypePayload(LeaveType $type): array
    {
        return [
            'id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
            'max_days_per_year' => $type->max_days_per_year,
            'paid' => $type->paid,
        ];
    }

    protected function leaveBalancePayload(LeaveBalance $balance): array
    {
        $balance->loadMissing('leaveType');

        return [
            'id' => $balance->id,
            'employee_id' => $balance->employee_id,
            'leave_type_id' => $balance->leave_type_id,
            'leave_type' => $balance->leaveType ? $this->leaveTypePayload($balance->leaveType) : null,
            'year' => $balance->year,
            'days_accrued' => (string) $balance->days_accrued,
            'days_used' => (string) $balance->days_used,
            'days_remaining' => (string) $balance->days_remaining,
            'closed' => $balance->closed,
        ];
    }

    protected function overtimeRatePayload(OvertimeRate $rate): array
    {
        return [
            'id' => $rate->id,
            'category' => $rate->category,
            'multiplier' => (string) $rate->multiplier,
        ];
    }

    protected function overtimeRecordPayload(OvertimeRecord $record): array
    {
        return [
            'id' => $record->id,
            'employee_id' => $record->employee_id,
            'work_date' => $record->work_date?->toDateString(),
            'hours' => (string) $record->hours,
            'category' => $record->category,
            'status' => $record->status,
            'payroll_run_id' => $record->payroll_run_id,
        ];
    }

    protected function loanPayload(LoanRecord $loan): array
    {
        return [
            'id' => $loan->id,
            'employee_id' => $loan->employee_id,
            'principal_amount' => (string) $loan->principal_amount,
            'monthly_repayment' => (string) $loan->monthly_repayment,
            'remaining_balance' => (string) $loan->remaining_balance,
            'status' => $loan->status,
            'disbursed_at' => $loan->disbursed_at?->toDateString(),
            's4_journal_entry_id' => $loan->s4_journal_entry_id,
        ];
    }

    protected function departmentPayload(Department $department): array
    {
        return [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'head_employee_id' => $department->head_employee_id,
        ];
    }

    protected function disciplinaryPayload(DisciplinaryRecord $record): array
    {
        $record->loadMissing('employee');

        return [
            'id' => $record->id,
            'employee_id' => $record->employee_id,
            'action_type' => $record->action_type,
            'reason' => $record->reason,
            'effective_date' => $record->effective_date?->toDateString(),
            'suspension_days' => $record->suspension_days,
            'issued_by' => $record->issued_by,
        ];
    }

    protected function assetTypePayload(AssetType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'description' => $type->description,
        ];
    }

    protected function employeeAssetPayload(EmployeeAsset $asset): array
    {
        $asset->loadMissing(['employee', 'assetType']);

        return [
            'id' => $asset->id,
            'employee_id' => $asset->employee_id,
            'asset_type_id' => $asset->asset_type_id,
            'asset_type' => $asset->assetType ? $this->assetTypePayload($asset->assetType) : null,
            'serial_number' => $asset->serial_number,
            'assigned_date' => $asset->assigned_date?->toDateString(),
            'returned_date' => $asset->returned_date?->toDateString(),
            'condition_on_return' => $asset->condition_on_return,
        ];
    }

    protected function guarantorPayload(Guarantor $guarantor): array
    {
        return [
            'id' => $guarantor->id,
            'employee_id' => $guarantor->employee_id,
            'full_name' => $guarantor->full_name,
            'national_id' => $guarantor->national_id,
            'phone' => $guarantor->phone,
            'address' => $guarantor->address,
            'relationship' => $guarantor->relationship,
            'letter_path' => $guarantor->letter_path,
        ];
    }
}
