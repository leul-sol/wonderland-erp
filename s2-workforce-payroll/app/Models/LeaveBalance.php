<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'days_accrued',
        'days_used',
        'days_remaining',
        'closed',
    ];

    protected function casts(): array
    {
        return [
            'days_accrued' => 'decimal:2',
            'days_used' => 'decimal:2',
            'days_remaining' => 'decimal:2',
            'closed' => 'boolean',
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
