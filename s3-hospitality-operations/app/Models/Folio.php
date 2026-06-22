<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folio extends Model
{
    protected $fillable = [
        'reservation_id',
        'status',
        'total_charges',
        'total_payments',
        'currency',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'total_charges' => 'decimal:2',
            'total_payments' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FolioLine::class);
    }

    public function balance(): float
    {
        return round((float) $this->total_charges - (float) $this->total_payments, 2);
    }
}
