<?php

namespace App\Services\Payroll;

class PensionService
{
    /**
     * @return array{employee: float, employer: float}
     */
    public function calculate(string $pensionCategory, float $basicSalary): array
    {
        if ($pensionCategory !== 'covered') {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        $employeeRate = (float) config('payroll.employee_pension_rate', 0.07);
        $employerRate = (float) config('payroll.employer_pension_rate', 0.11);

        return [
            'employee' => round($basicSalary * $employeeRate, 2),
            'employer' => round($basicSalary * $employerRate, 2),
        ];
    }
}
