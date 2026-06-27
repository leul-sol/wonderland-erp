<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function (JournalLine $line) {
            $line->loadMissing('journalEntry');
            if ($line->journalEntry?->status === 'posted') {
                throw new \RuntimeException('Posted journal lines are immutable.');
            }
        });

        static::deleting(function (JournalLine $line) {
            $line->loadMissing('journalEntry');
            if ($line->journalEntry?->status === 'posted') {
                throw new \RuntimeException('Posted journal lines cannot be deleted.');
            }
        });
    }

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
