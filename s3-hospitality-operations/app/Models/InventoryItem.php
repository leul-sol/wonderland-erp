<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'unit',
        'rotation_strategy',
        'is_perishable',
        'unit_cost',
        'quantity_on_hand',
        'reorder_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'quantity_on_hand' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'is_perishable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getCurrentStockAttribute()
    {
        return $this->quantity_on_hand;
    }

    public function getMinimumStockLevelAttribute()
    {
        return $this->reorder_level;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }
}
