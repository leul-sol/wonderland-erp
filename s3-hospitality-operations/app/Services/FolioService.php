<?php

namespace App\Services;

use App\Models\Folio;
use App\Models\FolioLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FolioService
{
    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
        private readonly TaxBreakdownService $tax,
    ) {
    }

    public function addCharge(Folio $folio, string $description, float $amount, string $category = 'room'): FolioLine
    {
        if ($folio->status !== 'open') {
            throw new InvalidArgumentException('Cannot charge a settled folio.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Charge amount must be positive.');
        }

        $idempotencyKey = 'folio-'.$folio->id.'-charge-'.(FolioLine::query()->where('folio_id', $folio->id)->where('line_type', 'charge')->count() + 1);
        $accounts = config('hospitality.accounts');

        $revenueAccount = match ($category) {
            'fb' => $accounts['fb_revenue'],
            'other' => $accounts['service_charge_revenue'],
            default => $accounts['room_revenue'],
        };

        $breakdown = $this->tax->compute($amount);

        return DB::transaction(function () use ($folio, $description, $amount, $category, $idempotencyKey, $revenueAccount, $breakdown, $accounts) {
            $journal = $this->s4->postJournal([
                'description' => $description,
                'source_module' => 's3',
                'source_reference' => 'FOLIO-'.$folio->id,
                'lines' => $this->tax->revenueJournalLines($accounts['ar_guest'], $revenueAccount, $breakdown),
            ], $idempotencyKey);

            $journalId = (string) ($journal['data']['id'] ?? '');

            $line = FolioLine::query()->create([
                'folio_id' => $folio->id,
                'line_type' => 'charge',
                'charge_category' => $category,
                'description' => $description,
                'subtotal' => $breakdown['subtotal'],
                'service_charge_rate' => $breakdown['service_charge_rate'],
                'service_charge_amount' => $breakdown['service_charge_amount'],
                'vat_rate' => $breakdown['vat_rate'],
                'vat_amount' => $breakdown['vat_amount'],
                'amount' => $breakdown['total_amount'],
                's4_journal_entry_id' => $journalId,
                'idempotency_key' => $idempotencyKey,
                'posted_at' => now(),
            ]);

            $folio->increment('total_charges', $breakdown['total_amount']);

            return $line;
        });
    }

    public function settle(Folio $folio, float $amount, string $paymentMethod = 'cash'): Folio
    {
        if ($folio->status !== 'open') {
            throw new InvalidArgumentException('Folio is already settled.');
        }

        $balance = $folio->balance();
        if ($balance <= 0) {
            $folio->update([
                'status' => 'settled',
                'settled_at' => now(),
            ]);

            return $folio->fresh('lines');
        }

        if ($amount < $balance) {
            throw new InvalidArgumentException('Payment does not cover folio balance.');
        }

        $idempotencyKey = 'folio-'.$folio->id.'-settle';

        $cashAccount = match ($paymentMethod) {
            'bank' => '1002',
            'pos' => '1004',
            'visa' => '1005',
            default => '1001',
        };

        return DB::transaction(function () use ($folio, $amount, $paymentMethod, $idempotencyKey, $cashAccount, $balance) {
            $this->s4->postJournal([
                'description' => 'Folio '.$folio->id.' settlement',
                'source_module' => 's3',
                'source_reference' => 'FOLIO-'.$folio->id.'-SETTLE',
                'lines' => [
                    ['account_code' => $cashAccount, 'debit' => $balance, 'credit' => 0],
                    ['account_code' => '1100', 'debit' => 0, 'credit' => $balance],
                ],
            ], $idempotencyKey);

            FolioLine::query()->create([
                'folio_id' => $folio->id,
                'line_type' => 'payment',
                'description' => 'Payment ('.$paymentMethod.')',
                'amount' => $balance,
                'payment_method' => $paymentMethod,
                'idempotency_key' => $idempotencyKey,
                'posted_at' => now(),
            ]);

            $folio->update([
                'total_payments' => $balance,
                'status' => 'settled',
                'settled_at' => now(),
            ]);

            $this->outbox->enqueue(config('events.channels.folio_settled'), [
                'folio_id' => $folio->id,
                'reservation_id' => $folio->reservation_id,
                'amount' => $balance,
                'payment_method' => $paymentMethod,
            ]);

            return $folio->fresh('lines');
        });
    }
}
