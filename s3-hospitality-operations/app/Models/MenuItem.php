<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'price',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'menu_item_ingredients')
            ->withPivot('quantity');
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(RestaurantOrderLine::class);
    }
}
