<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Folio;
use App\Models\FolioLine;
use App\Models\FolioPayment;
use App\Models\RestaurantOrder;
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

        return DB::transaction(function () use ($folio, $description, $category, $idempotencyKey, $revenueAccount, $breakdown, $accounts) {
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
            $folio->update(['outstanding_balance' => $folio->fresh()->balance()]);

            return $line;
        });
    }

    public function postChargeForRoom(RestaurantOrder $order, Bill $bill): FolioLine
    {
        if ($order->folio_id === null) {
            throw new InvalidArgumentException('Hotel guest order requires a folio.');
        }

        $folio = Folio::query()->findOrFail($order->folio_id);
        $description = 'Restaurant bill '.$bill->id.' (order '.$order->order_number.')';

        return $this->addCharge($folio, $description, (float) $bill->subtotal, 'fb');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(Folio $folio, array $data, int $cashierId): FolioPayment
    {
        if ($folio->status !== 'open') {
            throw new InvalidArgumentException('Folio is already settled.');
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        $balance = $folio->balance();
        if ($amount > $balance) {
            throw new InvalidArgumentException('Payment exceeds folio balance.');
        }

        $shiftId = (int) ($data['cashier_shift_id'] ?? 0);
        if ($shiftId <= 0) {
            throw new InvalidArgumentException('cashier_shift_id is required.');
        }

        $paymentMethod = (string) ($data['payment_method'] ?? 'cash');
        $idempotencyKey = (string) ($data['idempotency_key'] ?? 'folio-'.$folio->id.'-pay-'.($folio->payments()->count() + 1));

        $existing = FolioPayment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return $existing;
        }

        $cashAccount = match ($paymentMethod) {
            'bank_transfer' => '1002',
            'pos' => '1004',
            'visa' => '1005',
            default => '1001',
        };

        return DB::transaction(function () use ($folio, $amount, $paymentMethod, $cashierId, $shiftId, $idempotencyKey, $cashAccount, $balance) {
            $this->s4->postJournal([
                'description' => 'Folio '.$folio->id.' payment',
                'source_module' => 's3',
                'source_reference' => 'FOLIO-'.$folio->id.'-PAY',
                'lines' => [
                    ['account_code' => $cashAccount, 'debit' => $amount, 'credit' => 0],
                    ['account_code' => '1100', 'debit' => 0, 'credit' => $amount],
                ],
            ], $idempotencyKey);

            $payment = FolioPayment::query()->create([
                'folio_id' => $folio->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'cashier_id' => $cashierId,
                'cashier_shift_id' => $shiftId,
                'paid_at' => now(),
                'reference_number' => null,
                'idempotency_key' => $idempotencyKey,
            ]);

            FolioLine::query()->create([
                'folio_id' => $folio->id,
                'line_type' => 'payment',
                'description' => 'Payment ('.$paymentMethod.')',
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'idempotency_key' => $idempotencyKey,
                'posted_at' => now(),
            ]);

            $folio->increment('total_payments', $amount);
            $newBalance = $folio->fresh()->balance();
            $folio->update(['outstanding_balance' => max(0, $newBalance)]);

            if ($newBalance <= 0) {
                $folio->update([
                    'status' => 'settled',
                    'settled_at' => now(),
                ]);

                $this->outbox->enqueue(config('events.channels.folio_settled'), [
                    'folio_id' => $folio->id,
                    'reservation_id' => $folio->reservation_id,
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                ]);
            }

            return $payment->fresh();
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

        return DB::transaction(function () use ($folio, $paymentMethod, $idempotencyKey, $cashAccount, $balance) {
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
                'outstanding_balance' => 0,
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

    /**
     * @return array<string, mixed>
     */
    public function invoicePayload(Folio $folio): array
    {
        $folio->loadMissing(['lines', 'reservation.guest', 'guest', 'room.roomType']);

        $guest = $folio->guest ?? $folio->reservation?->guest;

        return [
            'folio_number' => $folio->folio_number,
            'guest_full_name' => $guest?->full_name ?? $folio->reservation?->guest_name,
            'guest_phone' => $guest?->phone ?? $folio->reservation?->guest_phone,
            'guest_email' => $guest?->email ?? $folio->reservation?->guest_email,
            'room_number' => $folio->room?->room_number,
            'check_in_date' => $folio->reservation?->check_in_date?->toDateString(),
            'check_out_date' => $folio->reservation?->check_out_date?->toDateString(),
            'total_charges' => (string) $folio->total_charges,
            'total_payments' => (string) $folio->total_payments,
            'outstanding_balance' => (string) $folio->outstanding_balance,
            'currency' => $folio->currency ?? 'ETB',
            'lines' => $folio->lines->map(fn ($line) => [
                'line_type' => $line->line_type,
                'description' => $line->description,
                'amount' => (string) $line->amount,
            ])->values(),
            'issued_at' => now()->toIso8601String(),
        ];
    }
}
