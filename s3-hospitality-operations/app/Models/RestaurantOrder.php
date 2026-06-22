<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantOrder extends Model
{
    protected $fillable = [
        'order_number',
        'folio_id',
        'status',
        'payment_context',
        'subtotal',
        'cogs_total',
        'revenue_journal_entry_id',
        'cogs_journal_entry_id',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'cogs_total' => 'decimal:2',
            'finalized_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RestaurantOrderLine::class);
    }
}
