<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeveranceCalculation extends Model
{
    protected $fillable = [
        'employee_id',
        'amount',
        'months_of_service',
        'calculation_date',
        'status',
        's4_journal_entry_id',
        's4_payout_journal_entry_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'calculation_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
