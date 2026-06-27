<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCacheLog extends Model
{
    public $timestamps = false;

    protected $table = 'report_cache_log';

    protected $fillable = [
        'report_key',
        'event',
        'ttl_seconds',
        'source_event',
        'cached_at',
        'invalidated_at',
    ];

    protected function casts(): array
    {
        return [
            'cached_at' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }
}
