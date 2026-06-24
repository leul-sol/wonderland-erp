<?php

namespace App\Http\Controllers\Concerns;

use App\Models\JournalEntry;

trait SerializesJournalEntries
{
    protected function journalPayload(JournalEntry $entry): array
    {
        $entry->loadMissing(['lines.account', 'fiscalPeriod']);

        return [
            'id' => $entry->id,
            'entry_number' => $entry->entry_number,
            'entry_date' => $entry->entry_date?->toDateString(),
            'description' => $entry->description,
            'source_module' => $entry->source_module,
            'source_reference' => $entry->source_reference,
            'status' => $entry->status,
            'total_debit' => (string) $entry->total_debit,
            'total_credit' => (string) $entry->total_credit,
            'fiscal_period_id' => $entry->fiscal_period_id,
            'fiscal_period' => $entry->fiscalPeriod ? [
                'year' => $entry->fiscalPeriod->year,
                'period_number' => $entry->fiscalPeriod->period_number,
                'status' => $entry->fiscalPeriod->status,
            ] : null,
            'posted_at' => $entry->posted_at?->toIso8601String(),
            'approved_by' => $entry->approved_by,
            'approved_at' => $entry->approved_at?->toIso8601String(),
            'second_approved_by' => $entry->second_approved_by,
            'second_approved_at' => $entry->second_approved_at?->toIso8601String(),
            'lines' => $entry->lines->map(fn ($line) => [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'account_code' => $line->account?->code,
                'account_name' => $line->account?->name,
                'debit' => (string) $line->debit,
                'credit' => (string) $line->credit,
                'description' => $line->description,
            ])->values()->all(),
        ];
    }
}
