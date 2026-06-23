<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffboardingRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'initiated_date',
        'reason',
        'last_working_day',
        'clearance_status',
        'severance_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initiated_date' => 'date',
            'last_working_day' => 'date',
            'severance_amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
