<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JournalEntry extends Model
{
    protected static function booted(): void
    {
        static::updating(function (JournalEntry $entry) {
            if ($entry->getOriginal('status') === 'posted') {
                throw new \RuntimeException('Posted journal entries are immutable.');
            }
        });

        static::deleting(function (JournalEntry $entry) {
            if ($entry->status === 'posted') {
                throw new \RuntimeException('Posted journal entries cannot be deleted.');
            }
        });
    }

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
        'second_approved_by',
        'second_approved_at',
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
            'second_approved_at' => 'datetime',
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

    public function reversalEntry(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function reversedEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function hasBeenReversed(): bool
    {
        if ($this->relationLoaded('reversalEntry')) {
            return $this->reversalEntry !== null;
        }

        return $this->reversalEntry()->exists();
    }
}
