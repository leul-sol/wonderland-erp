<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SeveranceCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SeveranceService
{
    public function __construct(private readonly OutboxService $outbox)
    {
    }

    public function calculate(Employee $employee): SeveranceCalculation
    {
        if ($employee->hire_date === null) {
            throw new InvalidArgumentException('Employee hire_date is required for severance calculation.');
        }

        return DB::transaction(function () use ($employee) {
            $hireDate = Carbon::parse($employee->hire_date);
            $months = max(1, (int) $hireDate->diffInMonths(now()));
            $monthlySalary = (float) $employee->base_salary;
            $amount = round($monthlySalary * $months * 0.5, 2);

            $calculation = SeveranceCalculation::query()->create([
                'employee_id' => $employee->id,
                'amount' => $amount,
                'months_of_service' => $months,
                'calculation_date' => now()->toDateString(),
                'status' => 'calculated',
            ]);

            $this->outbox->enqueue(config('events.channels.severance_calculated'), [
                'severance_id' => $calculation->id,
                'employee_id' => $employee->id,
                'amount' => (string) $amount,
                'months_of_service' => $months,
            ]);

            return $calculation->load('employee.department');
        });
    }
}
