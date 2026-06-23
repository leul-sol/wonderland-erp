<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'restaurant_order_id',
        'subtotal',
        'service_charge_rate',
        'service_charge_amount',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'paid_amount',
        'outstanding_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'service_charge_rate' => 'decimal:4',
            'service_charge_amount' => 'decimal:2',
            'vat_rate' => 'decimal:4',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
        ];
    }

    public function restaurantOrder(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }
}
