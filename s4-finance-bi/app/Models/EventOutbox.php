<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOutbox extends Model
{
    public $timestamps = false;

    protected $table = 'event_outbox';

    protected $fillable = [
        'event',
        'payload',
        'status',
        'attempts',
        'created_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
