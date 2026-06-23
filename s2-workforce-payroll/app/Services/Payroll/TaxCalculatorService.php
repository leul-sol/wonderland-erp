<?php

namespace App\Services\Payroll;

class TaxCalculatorService
{
    public function calculate(float $taxableIncome): float
    {
        $taxableIncome = max(0, round($taxableIncome, 2));

        foreach (config('payroll.erca_brackets', []) as $bracket) {
            $max = (float) $bracket['max'];
            $min = (float) $bracket['min'];

            if ($taxableIncome >= $min && $taxableIncome <= $max) {
                $tax = ($taxableIncome * (float) $bracket['rate']) - (float) $bracket['deduction'];

                return max(0, round($tax, 2));
            }
        }

        return 0.0;
    }
}
