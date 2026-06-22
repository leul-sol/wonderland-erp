<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeConsumptionPeriod extends Model
{
    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'total_amount',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_amount' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }
}
