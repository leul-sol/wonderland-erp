<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receivable extends Model
{
    protected $fillable = [
        'account_id',
        'party_name',
        'source_reference',
        'source_module',
        'original_amount',
        'balance',
        'status',
        'journal_entry_id',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'settled_at' => 'datetime',
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
