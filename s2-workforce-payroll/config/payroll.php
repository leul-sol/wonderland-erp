<?php

return [
    'employee_pension_rate' => (float) env('PAYROLL_EMPLOYEE_PENSION_RATE', 0.07),
    'employer_pension_rate' => (float) env('PAYROLL_EMPLOYER_PENSION_RATE', 0.11),
    'income_tax_rate' => (float) env('PAYROLL_INCOME_TAX_RATE', 0.15),
];
