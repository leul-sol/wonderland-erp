<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\PayrollRun;

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
                'net_pay' => (string) $line->net_pay,
            ])->values()->all(),
        ];
    }
}
