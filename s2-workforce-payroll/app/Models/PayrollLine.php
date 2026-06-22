<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollLine extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'gross_salary',
        'employee_pension',
        'employer_pension',
        'income_tax',
        'net_pay',
    ];

    protected function casts(): array
    {
        return [
            'gross_salary' => 'decimal:2',
            'employee_pension' => 'decimal:2',
            'employer_pension' => 'decimal:2',
            'income_tax' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
