<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FbDailySummary extends Model
{
    protected $fillable = [
        'business_date',
        'order_count',
        'subtotal',
        'service_charge_amount',
        'vat_amount',
        'total_amount',
        's4_journal_entry_id',
        'idempotency_key',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'subtotal' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }
}
