<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    protected $fillable = [
        'run_number',
        'period_start',
        'period_end',
        'status',
        'total_gross',
        'total_net',
        's4_journal_entry_id',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_gross' => 'decimal:2',
            'total_net' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }
}
