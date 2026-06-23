<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'principal_amount',
        'monthly_repayment',
        'remaining_balance',
        'status',
        'disbursed_at',
        's4_journal_entry_id',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'monthly_repayment' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'disbursed_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
