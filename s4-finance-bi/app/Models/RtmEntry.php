<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Runtime requirements traceability row.
 *
 * SDD §3.2 maps this to `rtm_requirements` (fr_code → requirement_key,
 * implementation_status → status, developer_notes → notes).
 */
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
