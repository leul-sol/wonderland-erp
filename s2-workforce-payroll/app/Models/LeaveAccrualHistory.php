<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAccrualHistory extends Model
{
    public $timestamps = false;

    protected $table = 'leave_accrual_history';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'days_accrued',
        'accrual_date',
    ];

    protected function casts(): array
    {
        return [
            'days_accrued' => 'decimal:2',
            'accrual_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
