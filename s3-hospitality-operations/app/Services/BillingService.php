<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\RestaurantOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BillingService
{
    public function __construct(
        private readonly FolioService $folio,
        private readonly EmployeeConsumptionService $employeeConsumption,
        private readonly S4FinanceClient $s4,
        private readonly TaxBreakdownService $tax,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function pay(Bill $bill, array $data, int $cashierId, ?int $shiftId): BillPayment
    {
        $amount = round((float) ($data['amount'] ?? $bill->outstanding_balance), 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > (float) $bill->outstanding_balance) {
            throw new InvalidArgumentException('Payment exceeds bill outstanding balance.');
        }

        $paymentMethod = (string) ($data['payment_method'] ?? 'cash');
        $idempotencyKey = (string) ($data['idempotency_key'] ?? 'bill-'.$bill->id.'-pay-'.($bill->payments()->count() + 1));

        $existing = BillPayment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return $existing;
        }

        $bill->loadMissing('restaurantOrder');

        return DB::transaction(function () use ($bill, $amount, $paymentMethod, $cashierId, $shiftId, $idempotencyKey) {
            $this->routeByCustomerType($bill, $amount, $paymentMethod, $idempotencyKey);

            $payment = BillPayment::query()->create([
                'bill_id' => $bill->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'cashier_id' => $cashierId,
                'cashier_shift_id' => $shiftId,
                'paid_at' => now(),
                'reference_number' => null,
                'idempotency_key' => $idempotencyKey,
            ]);

            $bill->increment('paid_amount', $amount);
            $outstanding = round((float) $bill->outstanding_balance - $amount, 2);
            $bill->update([
                'outstanding_balance' => max(0, $outstanding),
                'status' => $outstanding <= 0 ? 'paid' : 'partial',
            ]);

            return $payment->fresh();
        });
    }

    public function settleFromFinalize(Bill $bill, RestaurantOrder $order): void
    {
        if ($order->employee_consumption_period_id !== null) {
            $this->markBillPaid($bill);

            return;
        }

        $customerType = $order->customer_type ?? 'outside_cash';

        match ($customerType) {
            'employee', 'family_member', 'management' => $this->employeeConsumption->accumulateSubtotal(
                (int) ($order->customer_ref_id ?? 0),
                (float) $bill->subtotal,
            ),
            'outside_credit' => null,
            default => null,
        };

        if ($customerType !== 'outside_credit') {
            $this->markBillPaid($bill);
        }
    }

    private function markBillPaid(Bill $bill): void
    {
        $bill->update([
            'paid_amount' => $bill->total_amount,
            'outstanding_balance' => 0,
            'status' => 'paid',
        ]);
    }

    private function routeByCustomerType(Bill $bill, float $amount, string $paymentMethod, string $idempotencyKey): void
    {
        $order = $bill->restaurantOrder;
        $customerType = $order->customer_type ?? 'outside_cash';

        match ($customerType) {
            'hotel_guest' => $order->folio_id === null
                ? $this->folio->postChargeForRoom($order, $bill)
                : null,
            'employee', 'family_member', 'management' => $this->employeeConsumption->accumulateSubtotal(
                (int) ($order->customer_ref_id ?? 0),
                (float) $bill->subtotal,
            ),
            'outside_credit' => null,
            'event' => $this->postEventJournal($bill, $amount, $idempotencyKey),
            'outside_cash' => $this->postOutsideCashJournal($bill, $amount, $paymentMethod, $idempotencyKey),
            default => throw new InvalidArgumentException('Unsupported customer type: '.$customerType),
        };
    }

    private function postEventJournal(Bill $bill, float $amount, string $idempotencyKey): void
    {
        $accounts = config('hospitality.accounts');
        $breakdown = $this->tax->compute($amount);

        try {
            $this->s4->postJournal([
                'description' => 'Event F&B bill '.$bill->id,
                'source_module' => 's3',
                'source_reference' => 'BILL-'.$bill->id,
                'lines' => $this->tax->revenueJournalLines($accounts['ar_guest'], $accounts['fb_revenue'], $breakdown),
            ], $idempotencyKey.'-event');
        } catch (RuntimeException $e) {
            throw $e;
        }
    }

    private function postOutsideCashJournal(Bill $bill, float $amount, string $paymentMethod, string $idempotencyKey): void
    {
        $accounts = config('hospitality.accounts');
        $breakdown = $this->tax->compute($amount);

        $cashAccount = match ($paymentMethod) {
            'bank' => '1002',
            'pos' => '1004',
            'visa' => '1005',
            default => $accounts['cash'],
        };

        try {
            $this->s4->postJournal([
                'description' => 'Cash F&B bill '.$bill->id,
                'source_module' => 's3',
                'source_reference' => 'BILL-'.$bill->id,
                'lines' => $this->tax->revenueJournalLines($cashAccount, $accounts['fb_revenue'], $breakdown),
            ], $idempotencyKey.'-cash');
        } catch (RuntimeException $e) {
            throw $e;
        }
    }
}
