<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\EmployeeConsumptionPeriod;
use App\Models\Folio;
use App\Models\FolioLine;
use App\Models\MenuItem;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly BillingService $billing,
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
        private readonly TaxBreakdownService $tax,
        private readonly DailyFbSummaryService $dailyFbSummary,
    ) {
    }

    public function create(
        ?int $folioId = null,
        ?int $consumptionPeriodId = null,
        ?string $customerType = null,
        ?int $customerRefId = null,
        ?int $tableId = null,
    ): RestaurantOrder {
        if ($folioId !== null && $consumptionPeriodId !== null) {
            throw new InvalidArgumentException('An order cannot be linked to both a folio and an employee consumption period.');
        }

        if ($consumptionPeriodId !== null) {
            $period = EmployeeConsumptionPeriod::query()->findOrFail($consumptionPeriodId);

            if ($period->status !== 'open') {
                throw new InvalidArgumentException('Consumption period is not open.');
            }

            return RestaurantOrder::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'employee_consumption_period_id' => $period->id,
                'payment_context' => 'employee_meal',
                'customer_type' => 'employee',
                'customer_ref_id' => $period->employee_id,
                'status' => 'open',
                'opened_at' => now(),
            ]);
        }

        $resolvedCustomerType = $customerType ?? ($folioId !== null ? 'hotel_guest' : 'outside_cash');

        if ($folioId !== null) {
            $folio = Folio::query()->findOrFail($folioId);
            if ($folio->status !== 'open') {
                throw new InvalidArgumentException('Cannot attach order to a settled folio.');
            }
        }

        $resolvedRefId = $customerRefId;
        if ($resolvedRefId === null && $folioId !== null) {
            $resolvedRefId = Folio::query()->whereKey($folioId)->value('guest_id');
        }

        return RestaurantOrder::query()->create([
            'order_number' => $this->nextOrderNumber(),
            'folio_id' => $folioId,
            'dining_table_id' => $tableId,
            'customer_type' => $resolvedCustomerType,
            'customer_ref_id' => $resolvedRefId,
            'payment_context' => $folioId !== null ? 'folio' : 'cash',
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    public function addLine(RestaurantOrder $order, int $menuItemId, int $quantity = 1): RestaurantOrderLine
    {
        if ($order->status !== 'open') {
            throw new InvalidArgumentException('Cannot modify a finalized order.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }

        $menuItem = MenuItem::query()->where('is_active', true)->findOrFail($menuItemId);
        $lineTotal = round((float) $menuItem->price * $quantity, 2);

        return DB::transaction(function () use ($order, $menuItem, $quantity, $lineTotal) {
            $line = RestaurantOrderLine::query()->create([
                'restaurant_order_id' => $order->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $quantity,
                'unit_price' => $menuItem->price,
                'line_total' => $lineTotal,
            ]);

            $order->increment('subtotal', $lineTotal);

            return $line->load('menuItem');
        });
    }

    public function removeLine(RestaurantOrder $order, RestaurantOrderLine $line): RestaurantOrder
    {
        if ($order->status !== 'open') {
            throw new InvalidArgumentException('Cannot modify a finalized order.');
        }

        if ((int) $line->restaurant_order_id !== (int) $order->id) {
            throw new InvalidArgumentException('Order line does not belong to this order.');
        }

        return DB::transaction(function () use ($order, $line) {
            $order->decrement('subtotal', (float) $line->line_total);
            $line->delete();

            return $order->fresh(['lines.menuItem', 'folio']);
        });
    }

    public function cancel(RestaurantOrder $order): RestaurantOrder
    {
        if ($order->status !== 'open') {
            throw new InvalidArgumentException('Only open orders can be cancelled.');
        }

        $order->update([
            'status' => 'cancelled',
            'finalized_at' => now(),
        ]);

        return $order->fresh(['lines.menuItem', 'folio']);
    }

    public function finalize(RestaurantOrder $order): RestaurantOrder
    {
        if ($order->status !== 'open') {
            throw new InvalidArgumentException('Order is already finalized.');
        }

        $order->loadMissing(['lines.menuItem.ingredients', 'folio']);

        if ($order->lines->isEmpty()) {
            throw new InvalidArgumentException('Order has no lines.');
        }

        $accounts = config('hospitality.accounts');
        $subtotal = round((float) $order->subtotal, 2);
        $breakdown = $this->tax->compute($subtotal);

        return DB::transaction(function () use ($order, $accounts, $subtotal, $breakdown) {
            $cogsTotal = 0.0;

            foreach ($order->lines as $line) {
                $cogsTotal += $this->inventory->consumeForMenuItem(
                    $line->menuItem,
                    $line->quantity,
                    'restaurant_order',
                    $order->id
                );
            }

            $cogsTotal = round($cogsTotal, 2);

            $revenueJournal = null;
            if ($order->payment_context !== 'employee_meal' && ! $this->dailyFbSummary->shouldDeferRevenueJournal($order)) {
                $debitAccount = $order->payment_context === 'folio'
                    ? $accounts['ar_guest']
                    : $accounts['cash'];

                $revenueReference = $order->folio_id !== null
                    ? 'FOLIO-'.$order->folio_id
                    : 'ORDER-'.$order->id;

                $revenueJournal = $this->s4->postJournal([
                    'description' => 'F&B order '.$order->order_number,
                    'source_module' => 's3',
                    'source_reference' => $revenueReference,
                    'lines' => $this->tax->revenueJournalLines($debitAccount, $accounts['fb_revenue'], $breakdown),
                ], 'order-'.$order->id.'-revenue');
            }

            $cogsJournalId = null;
            if ($cogsTotal > 0) {
                $cogsJournal = $this->s4->postJournal([
                    'description' => 'COGS '.$order->order_number,
                    'source_module' => 's3',
                    'source_reference' => 'ORDER-'.$order->id.'-COGS',
                    'lines' => [
                        ['account_code' => $accounts['cogs_food'], 'debit' => $cogsTotal, 'credit' => 0],
                        ['account_code' => $accounts['inventory_fb'], 'debit' => 0, 'credit' => $cogsTotal],
                    ],
                ], 'order-'.$order->id.'-cogs');

                $cogsJournalId = (string) ($cogsJournal['data']['id'] ?? '');
            }

            if ($order->folio_id !== null && $revenueJournal !== null) {
                $folio = $order->folio;
                FolioLine::query()->create([
                    'folio_id' => $folio->id,
                    'line_type' => 'charge',
                    'charge_category' => 'fb',
                    'description' => 'Restaurant order '.$order->order_number,
                    'subtotal' => $breakdown['subtotal'],
                    'service_charge_rate' => $breakdown['service_charge_rate'],
                    'service_charge_amount' => $breakdown['service_charge_amount'],
                    'vat_rate' => $breakdown['vat_rate'],
                    'vat_amount' => $breakdown['vat_amount'],
                    'amount' => $breakdown['total_amount'],
                    's4_journal_entry_id' => (string) ($revenueJournal['data']['id'] ?? ''),
                    'idempotency_key' => 'order-'.$order->id.'-folio',
                    'posted_at' => now(),
                ]);
                $folio->increment('total_charges', $breakdown['total_amount']);
            }

            $order->update([
                'status' => 'finalized',
                'service_charge_amount' => $breakdown['service_charge_amount'],
                'vat_amount' => $breakdown['vat_amount'],
                'total_amount' => $breakdown['total_amount'],
                'cogs_total' => $cogsTotal,
                'revenue_journal_entry_id' => $revenueJournal !== null
                    ? (string) ($revenueJournal['data']['id'] ?? '')
                    : null,
                'cogs_journal_entry_id' => $cogsJournalId,
                'finalized_at' => now(),
            ]);

            $bill = Bill::query()->create([
                'restaurant_order_id' => $order->id,
                'subtotal' => $breakdown['subtotal'],
                'service_charge_rate' => $breakdown['service_charge_rate'],
                'service_charge_amount' => $breakdown['service_charge_amount'],
                'vat_rate' => $breakdown['vat_rate'],
                'vat_amount' => $breakdown['vat_amount'],
                'total_amount' => $breakdown['total_amount'],
                'paid_amount' => 0,
                'outstanding_balance' => $breakdown['total_amount'],
                'status' => 'unpaid',
            ]);

            $this->billing->settleFromFinalize($bill, $order->fresh());

            if ($order->employee_consumption_period_id !== null) {
                $this->syncConsumptionPeriodTotal((int) $order->employee_consumption_period_id);
            }

            $this->outbox->enqueue(config('events.channels.order_finalized'), [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'folio_id' => $order->folio_id,
                'subtotal' => (string) $subtotal,
                'total_amount' => (string) $breakdown['total_amount'],
                'cogs_total' => (string) $cogsTotal,
                'revenue_journal_entry_id' => $order->revenue_journal_entry_id,
                'cogs_journal_entry_id' => $order->cogs_journal_entry_id,
            ]);

            return $order->fresh(['lines.menuItem', 'folio']);
        });
    }

    private function syncConsumptionPeriodTotal(int $periodId): void
    {
        $total = (float) RestaurantOrder::query()
            ->where('employee_consumption_period_id', $periodId)
            ->where('status', 'finalized')
            ->sum('total_amount');

        EmployeeConsumptionPeriod::query()
            ->whereKey($periodId)
            ->update(['total_amount' => round($total, 2)]);
    }

    private function nextOrderNumber(): string
    {
        $last = RestaurantOrder::query()->orderByDesc('id')->lockForUpdate()->value('order_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/ORD-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'ORD-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
