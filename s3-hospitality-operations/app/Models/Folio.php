<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folio extends Model
{
    protected $fillable = [
        'folio_number',
        'reservation_id',
        'guest_id',
        'room_id',
        'status',
        'total_charges',
        'total_payments',
        'outstanding_balance',
        'currency',
        'settled_at',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'total_charges' => 'decimal:2',
            'total_payments' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'settled_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(GuestProfile::class, 'guest_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FolioLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FolioPayment::class);
    }

    public function balance(): float
    {
        return round((float) $this->total_charges - (float) $this->total_payments, 2);
    }
}
