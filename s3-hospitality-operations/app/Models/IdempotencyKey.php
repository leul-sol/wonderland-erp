<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'idempotency_key',
        'endpoint',
        'request_hash',
        'response_body',
        'status_code',
        'created_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'created_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
