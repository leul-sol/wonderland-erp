<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetType extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function employeeAssets(): HasMany
    {
        return $this->hasMany(EmployeeAsset::class);
    }
}
