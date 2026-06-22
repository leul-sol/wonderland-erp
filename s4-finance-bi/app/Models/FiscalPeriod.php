<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalPeriod extends Model
{
    protected $fillable = [
        'year',
        'period_number',
        'start_date',
        'end_date',
        'status',
        'closed_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'period_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function allowsPosting(): bool
    {
        return in_array($this->status, ['open', 'closing'], true);
    }
}
