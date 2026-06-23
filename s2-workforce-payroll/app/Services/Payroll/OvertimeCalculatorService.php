<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\OvertimeRate;
use App\Models\OvertimeRecord;
use Carbon\Carbon;

class OvertimeCalculatorService
{
    /**
     * @return array{amount: float, record_ids: list<int>}
     */
    public function calculateForPeriod(Employee $employee, string $periodStart, string $periodEnd): array
    {
        $rates = OvertimeRate::query()->pluck('multiplier', 'category');
        $basicSalary = (float) $employee->base_salary;
        $workingDays = (int) config('payroll.working_days_per_month', 26);
        $workingHours = (int) config('payroll.working_hours_per_day', 8);
        $hourlyRate = $workingDays > 0 && $workingHours > 0
            ? $basicSalary / ($workingDays * $workingHours)
            : 0.0;

        $records = OvertimeRecord::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereNull('payroll_run_id')
            ->whereDate('work_date', '>=', $periodStart)
            ->whereDate('work_date', '<=', $periodEnd)
            ->get();

        $total = 0.0;
        $ids = [];

        foreach ($records as $record) {
            $multiplier = (float) ($rates[$record->category] ?? 1.0);
            $total += $hourlyRate * $multiplier * (float) $record->hours;
            $ids[] = $record->id;
        }

        return [
            'amount' => round($total, 2),
            'record_ids' => $ids,
        ];
    }

    public function markPaid(array $recordIds, int $payrollRunId): void
    {
        if ($recordIds === []) {
            return;
        }

        OvertimeRecord::query()
            ->whereIn('id', $recordIds)
            ->update([
                'status' => 'paid',
                'payroll_run_id' => $payrollRunId,
            ]);
    }
}
