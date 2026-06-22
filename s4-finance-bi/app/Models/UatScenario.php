<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UatScenario extends Model
{
    protected $fillable = [
        'scenario_key',
        'system',
        'title',
        'requirement_key',
        'preconditions',
        'steps',
        'expected_outcome',
        'status',
        'executed_by',
        'executed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }
}
