<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalEvent extends Model
{
    protected $fillable = [
        'channel',
        'source_system',
        'request_id',
        'payload',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
