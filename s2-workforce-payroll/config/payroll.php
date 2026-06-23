<?php

return [
    'employee_pension_rate' => (float) env('PAYROLL_EMPLOYEE_PENSION_RATE', 0.07),
    'employer_pension_rate' => (float) env('PAYROLL_EMPLOYER_PENSION_RATE', 0.11),
    'working_days_per_month' => (int) env('PAYROLL_WORKING_DAYS_PER_MONTH', 26),
    'working_hours_per_day' => (int) env('PAYROLL_WORKING_HOURS_PER_DAY', 8),
    'exclude_weekends_from_leave' => filter_var(env('EXCLUDE_WEEKENDS_FROM_LEAVE', true), FILTER_VALIDATE_BOOL),

    // ERCA progressive income tax brackets (monthly taxable income in ETB).
    'erca_brackets' => [
        ['min' => 0, 'max' => 600, 'rate' => 0.00, 'deduction' => 0],
        ['min' => 601, 'max' => 1650, 'rate' => 0.10, 'deduction' => 0],
        ['min' => 1651, 'max' => 3200, 'rate' => 0.15, 'deduction' => 90],
        ['min' => 3201, 'max' => 5250, 'rate' => 0.20, 'deduction' => 240],
        ['min' => 5251, 'max' => 7800, 'rate' => 0.25, 'deduction' => 502.50],
        ['min' => 7801, 'max' => 10900, 'rate' => 0.30, 'deduction' => 892.50],
        ['min' => 10901, 'max' => PHP_FLOAT_MAX, 'rate' => 0.35, 'deduction' => 1437.50],
    ],

    'severance_schedule' => [
        ['min_years' => 0, 'max_years' => 1, 'days' => 0],
        ['min_years' => 1, 'max_years' => 2, 'days' => 30],
        ['min_years' => 3, 'max_years' => 5, 'days' => 45],
        ['min_years' => 6, 'max_years' => 10, 'days' => 60],
        ['min_years' => 11, 'max_years' => 20, 'days' => 90],
        ['min_years' => 21, 'max_years' => PHP_INT_MAX, 'days' => 120],
    ],
];
