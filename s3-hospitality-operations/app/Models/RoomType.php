<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'base_rate',
        'max_occupancy',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
