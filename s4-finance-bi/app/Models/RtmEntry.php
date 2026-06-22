<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RtmEntry extends Model
{
    protected $fillable = [
        'requirement_key',
        'system',
        'domain',
        'title',
        'description',
        'spec_section',
        'status',
        'priority',
        'notes',
        'updated_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }
}
