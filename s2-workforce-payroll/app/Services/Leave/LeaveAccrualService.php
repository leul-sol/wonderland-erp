<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveAccrualHistory;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveAccrualService
{
    public function runForDate(Carbon $date): int
    {
        $alType = LeaveType::query()->where('code', 'AL')->first();

        if ($alType === null) {
            return 0;
        }

        $processed = 0;
        $employees = Employee::query()
            ->whereIn('status', ['active', 'on_leave'])
            ->whereNotNull('hire_date')
            ->get();

        foreach ($employees as $employee) {
            $hireDate = Carbon::parse($employee->hire_date);

            if ($hireDate->format('m-d') !== $date->format('m-d')) {
                continue;
            }

            $completedYears = max(0, $hireDate->diffInYears($date) - 1);

            if ($completedYears < 1) {
                continue;
            }

            $annualDays = min(15 + $completedYears, 30);
            $year = (int) $date->format('Y');

            DB::transaction(function () use ($employee, $alType, $annualDays, $year, $date) {
                $balance = LeaveBalance::query()->firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $alType->id,
                        'year' => $year,
                    ],
                    [
                        'days_accrued' => 0,
                        'days_used' => 0,
                        'days_remaining' => 0,
                        'closed' => false,
                    ]
                );

                $balance->update([
                    'days_accrued' => round((float) $balance->days_accrued + $annualDays, 2),
                    'days_remaining' => round((float) $balance->days_remaining + $annualDays, 2),
                ]);

                LeaveAccrualHistory::query()->create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $alType->id,
                    'year' => $year,
                    'days_accrued' => $annualDays,
                    'accrual_date' => $date->toDateString(),
                ]);
            });

            $processed++;
        }

        return $processed;
    }
}
