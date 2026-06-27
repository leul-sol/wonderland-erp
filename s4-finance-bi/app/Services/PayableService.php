<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Payable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayableService
{
    public function __construct(private readonly FinanceAuditLogger $audit)
    {
    }

    public function applyPostedEntry(JournalEntry $entry): void
    {
        if ($entry->status !== 'posted') {
            return;
        }

        $entry->loadMissing('lines.account');
        $apCode = config('finance.ap_account_code', '2001');

        foreach ($entry->lines as $line) {
            if ($line->account?->code !== $apCode) {
                continue;
            }

            $reference = $entry->source_reference ?? $entry->entry_number;
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;

            if ($credit > 0) {
                $this->increaseBalance($line->account_id, $reference, $entry->source_module, $credit, $entry->id);
            }

            if ($debit > 0) {
                $this->decreaseBalance($line->account_id, $reference, $debit);
            }
        }
    }

    public function settle(Payable $payable, float $amount, string $paymentMethod, int $userId): Payable
    {
        if (! in_array($payable->status, ['open', 'partial'], true)) {
            throw new InvalidArgumentException('Payable cannot be settled in its current status.');
        }

        if ($amount <= 0 || round($amount, 2) > round((float) $payable->balance, 2)) {
            throw new InvalidArgumentException('Settlement amount is invalid.');
        }

        $cashAccount = match ($paymentMethod) {
            'bank' => '1002',
            default => '1001',
        };

        return DB::transaction(function () use ($payable, $amount, $userId, $cashAccount, $paymentMethod) {
            app(JournalService::class)->postImmediate([
                'description' => 'AP settlement '.$payable->source_reference,
                'source_module' => 'manual',
                'source_reference' => $payable->source_reference,
                'lines' => [
                    ['account_code' => '2001', 'debit' => $amount, 'credit' => 0],
                    ['account_code' => $cashAccount, 'debit' => 0, 'credit' => $amount],
                ],
            ], 'ap-settle-'.$payable->id.'-'.now()->timestamp, $userId);

            $updated = $payable->fresh('account');
            $this->audit->log('payable.settle', 'payable', $payable->id, $userId, [
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ]);

            return $updated;
        });
    }

    private function increaseBalance(int $accountId, string $reference, string $module, float $amount, int $journalEntryId): void
    {
        $payable = Payable::query()->firstOrNew([
            'source_reference' => $reference,
            'account_id' => $accountId,
        ]);

        if (! $payable->exists) {
            $payable->fill([
                'supplier_id' => $this->parseSupplierId($reference),
                'vendor_name' => $reference,
                'source_module' => $module,
                'original_amount' => $amount,
                'balance' => $amount,
                'status' => 'open',
                'journal_entry_id' => $journalEntryId,
            ]);
            $payable->save();

            return;
        }

        $payable->increment('balance', $amount);
        $payable->update(['status' => 'open', 'settled_at' => null]);
    }

    private function decreaseBalance(int $accountId, string $reference, float $amount): void
    {
        $payable = Payable::query()
            ->where('source_reference', $reference)
            ->where('account_id', $accountId)
            ->first();

        if ($payable === null) {
            return;
        }

        $newBalance = round((float) $payable->balance - $amount, 2);
        $status = $newBalance <= 0 ? 'settled' : 'partial';

        $payable->update([
            'balance' => max(0, $newBalance),
            'status' => $status,
            'settled_at' => $newBalance <= 0 ? now() : null,
        ]);
    }

    private function parseSupplierId(string $reference): ?int
    {
        if (preg_match('/supplier_id:(\d+)/', $reference, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
