<?php

namespace App\Services;

use App\Models\FbDailySummary;
use App\Models\RestaurantOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DailyFbSummaryService
{
    public function __construct(
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
    ) {
    }

    public function shouldDeferRevenueJournal(RestaurantOrder $order): bool
    {
        if ($order->payment_context === 'employee_meal') {
            return false;
        }

        if ($order->customer_type === 'hotel_guest' || $order->folio_id !== null) {
            return false;
        }

        return true;
    }

    public function run(?Carbon $businessDate = null): ?FbDailySummary
    {
        $businessDate = ($businessDate ?? now())->copy()->startOfDay();
        $dateString = $businessDate->toDateString();
        $idempotencyKey = 'fb-daily-summary:'.$dateString;

        $existing = FbDailySummary::query()->where('business_date', $dateString)->first();
        if ($existing !== null) {
            return $existing;
        }

        $orders = $this->pendingOrdersForDate($businessDate);
        if ($orders->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($orders, $businessDate, $dateString, $idempotencyKey) {
            $accounts = config('hospitality.accounts');
            $subtotal = round((float) $orders->sum('subtotal'), 2);
            $serviceCharge = round((float) $orders->sum('service_charge_amount'), 2);
            $vat = round((float) $orders->sum('vat_amount'), 2);
            $total = round($subtotal + $serviceCharge + $vat, 2);

            $debitLines = $this->debitLinesForOrders($orders);
            $lines = array_merge($debitLines, [
                ['account_code' => $accounts['fb_revenue'], 'debit' => 0, 'credit' => $subtotal],
                ['account_code' => $accounts['service_charge_revenue'], 'debit' => 0, 'credit' => $serviceCharge],
                ['account_code' => $accounts['vat_payable'], 'debit' => 0, 'credit' => $vat],
            ]);

            $journal = $this->s4->postJournal([
                'description' => 'Daily F&B summary '.$dateString,
                'source_module' => 's3',
                'source_reference' => 'FB-DAILY:'.$dateString,
                'entry_date' => $dateString,
                'lines' => $lines,
            ], $idempotencyKey);

            $journalId = (string) ($journal['data']['id'] ?? '');

            RestaurantOrder::query()
                ->whereIn('id', $orders->pluck('id'))
                ->update(['revenue_journal_entry_id' => $journalId]);

            $summary = FbDailySummary::query()->create([
                'business_date' => $dateString,
                'order_count' => $orders->count(),
                'subtotal' => $subtotal,
                'service_charge_amount' => $serviceCharge,
                'vat_amount' => $vat,
                'total_amount' => $total,
                's4_journal_entry_id' => $journalId,
                'idempotency_key' => $idempotencyKey,
                'posted_at' => now(),
            ]);

            $this->outbox->enqueue(config('events.channels.fb_daily_summary_posted'), [
                'business_date' => $dateString,
                'order_count' => $orders->count(),
                'total_amount' => (string) $total,
                's4_journal_entry_id' => $journalId,
            ]);

            return $summary;
        });
    }

    /**
     * @return Collection<int, RestaurantOrder>
     */
    private function pendingOrdersForDate(Carbon $businessDate): Collection
    {
        return RestaurantOrder::query()
            ->where('status', 'finalized')
            ->whereDate('finalized_at', $businessDate->toDateString())
            ->where('customer_type', '!=', 'hotel_guest')
            ->whereNull('folio_id')
            ->where('payment_context', '!=', 'employee_meal')
            ->whereNull('revenue_journal_entry_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, RestaurantOrder>  $orders
     * @return list<array{account_code: string, debit: float, credit: float}>
     */
    private function debitLinesForOrders(Collection $orders): array
    {
        $totals = [];

        foreach ($orders as $order) {
            $account = $this->debitAccountForCustomerType((string) $order->customer_type);
            $amount = round((float) $order->total_amount, 2);
            $totals[$account] = round(($totals[$account] ?? 0) + $amount, 2);
        }

        $lines = [];
        foreach ($totals as $accountCode => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'account_code' => $accountCode,
                'debit' => $amount,
                'credit' => 0,
            ];
        }

        if ($lines === []) {
            throw new InvalidArgumentException('Daily F&B summary has no debit lines.');
        }

        return $lines;
    }

    private function debitAccountForCustomerType(string $customerType): string
    {
        $accounts = config('hospitality.accounts');

        return match ($customerType) {
            'outside_credit', 'event' => '1101',
            default => $accounts['cash'],
        };
    }
}
