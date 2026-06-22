<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\SeveranceCalculation;

trait SerializesWorkforceResources
{
    protected function employeePayload(Employee $employee): array
    {
        $employee->loadMissing('department');

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
            'job_title' => $employee->job_title,
            'base_salary' => (string) $employee->base_salary,
            'default_role' => $employee->default_role,
            'status' => $employee->status,
            'hire_date' => $employee->hire_date?->toDateString(),
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
        ];
    }
}
