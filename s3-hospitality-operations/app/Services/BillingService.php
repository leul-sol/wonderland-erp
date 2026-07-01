<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\RestaurantOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BillingService
{
    public function __construct(
        private readonly FolioService $folio,
        private readonly EmployeeConsumptionService $employeeConsumption,
        private readonly DailyFbSummaryService $dailyFbSummary,
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
            $this->routeByCustomerType($bill, $paymentMethod);

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

    public function settleFromFinalize(Bill $bill, RestaurantOrder $order, ?int $cashierId = null, ?int $cashierShiftId = null): void
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

            if (
                $customerType === 'outside_cash'
                && $cashierShiftId !== null
                && $cashierShiftId > 0
            ) {
                BillPayment::query()->create([
                    'bill_id' => $bill->id,
                    'amount' => $bill->total_amount,
                    'payment_method' => 'cash',
                    'cashier_id' => $cashierId,
                    'cashier_shift_id' => $cashierShiftId,
                    'paid_at' => now(),
                    'reference_number' => null,
                    'idempotency_key' => 'bill-'.$bill->id.'-finalize-cash',
                ]);
            }
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

    private function routeByCustomerType(Bill $bill, string $paymentMethod): void
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
            'outside_credit', 'outside_cash', 'event' => $this->dailyFbSummary->shouldDeferRevenueJournal($order)
                ? null
                : throw new InvalidArgumentException('Unexpected billing path for '.$customerType),
            default => throw new InvalidArgumentException('Unsupported customer type: '.$customerType),
        };
    }
}
