<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    public function initialiseForEmployee(Employee $employee): void
    {
        $year = (int) now()->format('Y');
        $types = LeaveType::query()->get();

        DB::transaction(function () use ($employee, $types, $year) {
            foreach ($types as $type) {
                LeaveBalance::query()->firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    [
                        'days_accrued' => 0,
                        'days_used' => 0,
                        'days_remaining' => 0,
                        'closed' => false,
                    ]
                );
            }
        });
    }

    public function closeForEmployee(Employee $employee): void
    {
        LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('closed', false)
            ->update(['closed' => true]);
    }

    public function deductDays(Employee $employee, string $leaveTypeCode, float $days): void
    {
        $type = LeaveType::query()->where('code', $leaveTypeCode)->first();

        if ($type === null) {
            return;
        }

        $year = (int) now()->format('Y');
        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $year)
            ->first();

        if ($balance === null) {
            return;
        }

        $balance->update([
            'days_used' => round((float) $balance->days_used + $days, 2),
            'days_remaining' => max(0, round((float) $balance->days_remaining - $days, 2)),
        ]);
    }

    public function unusedAnnualLeaveDays(Employee $employee): float
    {
        $type = LeaveType::query()->where('code', 'AL')->first();

        if ($type === null) {
            return 0.0;
        }

        $year = (int) now()->format('Y');
        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $type->id)
            ->where('year', $year)
            ->first();

        return $balance ? (float) $balance->days_remaining : 0.0;
    }
}
