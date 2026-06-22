<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioLine extends Model
{
    protected $fillable = [
        'folio_id',
        'line_type',
        'charge_category',
        'description',
        'amount',
        'payment_method',
        's4_journal_entry_id',
        'idempotency_key',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }
}
