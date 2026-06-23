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
        'subtotal',
        'service_charge_rate',
        'service_charge_amount',
        'vat_rate',
        'vat_amount',
        'amount',
        'payment_method',
        's4_journal_entry_id',
        'idempotency_key',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'service_charge_rate' => 'decimal:4',
            'service_charge_amount' => 'decimal:2',
            'vat_rate' => 'decimal:4',
            'vat_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }
}
