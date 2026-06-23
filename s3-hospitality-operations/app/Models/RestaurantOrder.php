<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RestaurantOrder extends Model
{
    protected $fillable = [
        'order_number',
        'folio_id',
        'dining_table_id',
        'customer_type',
        'customer_ref_id',
        'cashier_id',
        'opened_at',
        'employee_consumption_period_id',
        'status',
        'payment_context',
        'subtotal',
        'service_charge_amount',
        'vat_amount',
        'total_amount',
        'cogs_total',
        'revenue_journal_entry_id',
        'cogs_journal_entry_id',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cogs_total' => 'decimal:2',
            'opened_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    public function folio(): BelongsTo
    {
        return $this->belongsTo(Folio::class);
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function consumptionPeriod(): BelongsTo
    {
        return $this->belongsTo(EmployeeConsumptionPeriod::class, 'employee_consumption_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RestaurantOrderLine::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }
}
