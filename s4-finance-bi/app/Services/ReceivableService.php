<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Receivable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ReceivableService
{
    public function applyPostedEntry(JournalEntry $entry): void
    {
        if ($entry->status !== 'posted') {
            return;
        }

        $entry->loadMissing('lines.account');
        $arCodes = config('finance.ar_account_codes', ['1100', '1101']);

        foreach ($entry->lines as $line) {
            $code = $line->account?->code;
            if ($code === null || ! in_array($code, $arCodes, true)) {
                continue;
            }

            $reference = $entry->source_reference ?? $entry->entry_number;
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($debit > 0) {
                $this->increaseBalance($line->account_id, $reference, $entry->source_module, $debit, $entry->id);
            }

            if ($credit > 0) {
                $this->decreaseBalance($line->account_id, $reference, $credit);
            }
        }
    }

    public function settle(Receivable $receivable, float $amount, string $paymentMethod, int $userId): Receivable
    {
        if ($receivable->status !== 'open') {
            throw new InvalidArgumentException('Receivable is already settled.');
        }

        if ($amount <= 0 || round($amount, 2) > round((float) $receivable->balance, 2)) {
            throw new InvalidArgumentException('Settlement amount is invalid.');
        }

        $cashAccount = match ($paymentMethod) {
            'bank' => '1002',
            'pos' => '1004',
            'visa' => '1005',
            default => '1001',
        };

        $accountCode = $receivable->account?->code ?? Account::query()->whereKey($receivable->account_id)->value('code');

        return DB::transaction(function () use ($receivable, $amount, $paymentMethod, $userId, $cashAccount, $accountCode) {
            app(JournalService::class)->postImmediate([
                'description' => 'AR settlement '.$receivable->source_reference,
                'source_module' => 'manual',
                'source_reference' => $receivable->source_reference,
                'lines' => [
                    ['account_code' => $cashAccount, 'debit' => $amount, 'credit' => 0],
                    ['account_code' => $accountCode, 'debit' => 0, 'credit' => $amount],
                ],
            ], 'ar-settle-'.$receivable->id.'-'.now()->timestamp, $userId);

            return $receivable->fresh('account');
        });
    }

    private function increaseBalance(int $accountId, string $reference, string $module, float $amount, int $journalEntryId): void
    {
        $receivable = Receivable::query()->firstOrNew([
            'source_reference' => $reference,
            'account_id' => $accountId,
        ]);

        if (! $receivable->exists) {
            $receivable->fill([
                'party_name' => $reference,
                'source_module' => $module,
                'original_amount' => $amount,
                'balance' => $amount,
                'status' => 'open',
                'journal_entry_id' => $journalEntryId,
            ]);
            $receivable->save();

            return;
        }

        $receivable->increment('balance', $amount);
        $receivable->update(['status' => 'open', 'settled_at' => null]);
    }

    private function decreaseBalance(int $accountId, string $reference, float $amount): void
    {
        $receivable = Receivable::query()
            ->where('source_reference', $reference)
            ->where('account_id', $accountId)
            ->first();

        if ($receivable === null) {
            return;
        }

        $newBalance = round((float) $receivable->balance - $amount, 2);
        $receivable->update([
            'balance' => max(0, $newBalance),
            'status' => $newBalance <= 0 ? 'settled' : 'open',
            'settled_at' => $newBalance <= 0 ? now() : null,
        ]);
    }
}
