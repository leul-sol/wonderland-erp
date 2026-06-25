<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOutbox extends Model
{
    public $timestamps = false;

    const UPDATED_AT = null;

    protected $table = 'event_outbox';

    protected $fillable = [
        'event',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'created_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
