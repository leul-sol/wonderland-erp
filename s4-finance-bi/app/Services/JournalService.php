<?php

namespace App\Services;

use App\Exceptions\ClosedPeriodException;
use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\UnbalancedJournalException;
use App\Models\Account;
use App\Models\IdempotencyKey;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalService
{
    public function __construct(
        private readonly FiscalPeriodService $fiscalPeriods,
        private readonly BiCacheService $biCache,
    ) {
    }

    /**
     * @param  array<int, array{account_id?: int, account_code?: string, debit?: float|int|string, credit?: float|int|string, description?: string|null}>  $lines
     */
    public function post(array $payload, ?string $idempotencyKey, ?int $createdBy = 0): JournalEntry
    {
        if ($idempotencyKey !== null) {
            $existing = $this->resolveIdempotent($idempotencyKey, $payload);
            if ($existing !== null) {
                return $existing;
            }
        }

        $sourceModule = (string) ($payload['source_module'] ?? 'manual');
        if (in_array($sourceModule, ['s2', 's3'], true) && ($idempotencyKey === null || $idempotencyKey === '')) {
            throw new \InvalidArgumentException('Idempotency-Key is required for automated journal entries.');
        }

        $lines = $payload['lines'] ?? [];
        [$totalDebit, $totalCredit, $normalizedLines] = $this->normalizeLines($lines);

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            throw new UnbalancedJournalException('Journal entry is not balanced.');
        }

        $entryDate = $payload['entry_date'] ?? now()->toDateString();
        $period = $this->fiscalPeriods->forDate($entryDate);
        $this->fiscalPeriods->assertAllowsPosting($period);

        $status = $sourceModule === 'manual' ? 'draft' : 'posted';

        return DB::transaction(function () use ($payload, $idempotencyKey, $createdBy, $sourceModule, $totalDebit, $totalCredit, $normalizedLines, $entryDate, $period, $status) {
            $entry = JournalEntry::query()->create([
                'entry_number' => $this->nextEntryNumber(),
                'entry_date' => $entryDate,
                'description' => Str::limit((string) ($payload['description'] ?? 'Journal entry'), 255, ''),
                'source_module' => $sourceModule,
                'source_reference' => $payload['source_reference'] ?? null,
                'status' => $status,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'idempotency_key' => $idempotencyKey,
                'fiscal_period_id' => $period->id,
                'posted_at' => $status === 'posted' ? now() : null,
                'created_by' => $createdBy ?? 0,
            ]);

            foreach ($normalizedLines as $line) {
                JournalLine::query()->create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'description' => $line['description'],
                ]);
            }

            if ($idempotencyKey !== null) {
                IdempotencyKey::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'request_hash' => $this->hashPayload($payload),
                    'journal_entry_id' => $entry->id,
                    'expires_at' => now()->addDay(),
                ]);
            }

            if ($status === 'posted') {
                $this->biCache->invalidate($period->id);
            }

            return $entry->load('lines.account', 'fiscalPeriod');
        });
    }

    private function resolveIdempotent(string $key, array $payload): ?JournalEntry
    {
        $stored = IdempotencyKey::query()->where('idempotency_key', $key)->first();

        if ($stored === null) {
            return null;
        }

        if ($stored->request_hash !== $this->hashPayload($payload)) {
            throw new IdempotencyConflictException('Idempotency key was already used with a different payload.');
        }

        $entry = $stored->journalEntry()->with('lines.account', 'fiscalPeriod')->first();
        if ($entry !== null) {
            $entry->replayed = true;
        }

        return $entry;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{0: float, 1: float, 2: array<int, array{account_id: int, debit: float, credit: float, description: ?string}>}
     */
    private function normalizeLines(array $lines): array
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $normalized = [];

        foreach ($lines as $line) {
            $accountId = $line['account_id'] ?? null;
            if ($accountId === null && isset($line['account_code'])) {
                $accountId = Account::query()->where('code', $line['account_code'])->value('id');
            }

            if ($accountId === null) {
                throw new \InvalidArgumentException('Each journal line requires account_id or account_code.');
            }

            $account = Account::query()->find($accountId);
            if ($account === null || ! $account->is_active) {
                throw new \InvalidArgumentException('Account '.$accountId.' is invalid or inactive.');
            }

            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);
            $totalDebit += $debit;
            $totalCredit += $credit;

            $normalized[] = [
                'account_id' => (int) $accountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $line['description'] ?? null,
            ];
        }

        return [$totalDebit, $totalCredit, $normalized];
    }

    private function nextEntryNumber(): string
    {
        $last = JournalEntry::query()->orderByDesc('id')->lockForUpdate()->value('entry_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/JE-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'JE-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
