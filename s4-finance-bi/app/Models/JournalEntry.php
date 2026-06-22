<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    public bool $replayed = false;

    protected $fillable = [
        'entry_number',
        'entry_date',
        'description',
        'source_module',
        'source_reference',
        'status',
        'total_debit',
        'total_credit',
        'idempotency_key',
        'fiscal_period_id',
        'reversal_of_id',
        'approved_by',
        'approved_at',
        'posted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
