<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    protected $fillable = [
        'cashier_id',
        'opened_at',
        'closed_at',
        'opening_cash_float',
        'closing_cash_counted',
        'expected_cash',
        'variance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash_float' => 'decimal:2',
            'closing_cash_counted' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function billPayments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    public function folioPayments(): HasMany
    {
        return $this->hasMany(FolioPayment::class);
    }
}
