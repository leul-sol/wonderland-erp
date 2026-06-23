<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeConsumption extends Model
{
    protected $table = 'employee_consumption';

    protected $fillable = [
        'employee_id',
        'period',
        'total_amount',
        'pushed_to_payroll',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'pushed_to_payroll' => 'boolean',
        ];
    }
}
