<?php

namespace App\Services;

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
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
    ) {
    }

    public function create(?int $folioId = null): RestaurantOrder
    {
        if ($folioId !== null) {
            $folio = Folio::query()->findOrFail($folioId);
            if ($folio->status !== 'open') {
                throw new InvalidArgumentException('Cannot attach order to a settled folio.');
            }
        }

        return RestaurantOrder::query()->create([
            'order_number' => $this->nextOrderNumber(),
            'folio_id' => $folioId,
            'payment_context' => $folioId !== null ? 'folio' : 'cash',
            'status' => 'open',
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

        return DB::transaction(function () use ($order, $accounts, $subtotal) {
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
                'lines' => [
                    ['account_code' => $debitAccount, 'debit' => $subtotal, 'credit' => 0],
                    ['account_code' => $accounts['fb_revenue'], 'debit' => 0, 'credit' => $subtotal],
                ],
            ], 'order-'.$order->id.'-revenue');

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

            if ($order->folio_id !== null) {
                $folio = $order->folio;
                FolioLine::query()->create([
                    'folio_id' => $folio->id,
                    'line_type' => 'charge',
                    'charge_category' => 'fb',
                    'description' => 'Restaurant order '.$order->order_number,
                    'amount' => $subtotal,
                    's4_journal_entry_id' => (string) ($revenueJournal['data']['id'] ?? ''),
                    'idempotency_key' => 'order-'.$order->id.'-folio',
                    'posted_at' => now(),
                ]);
                $folio->increment('total_charges', $subtotal);
            }

            $order->update([
                'status' => 'finalized',
                'cogs_total' => $cogsTotal,
                'revenue_journal_entry_id' => (string) ($revenueJournal['data']['id'] ?? ''),
                'cogs_journal_entry_id' => $cogsJournalId,
                'finalized_at' => now(),
            ]);

            $this->outbox->enqueue(config('events.channels.order_finalized'), [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'folio_id' => $order->folio_id,
                'subtotal' => (string) $subtotal,
                'cogs_total' => (string) $cogsTotal,
                'revenue_journal_entry_id' => $order->revenue_journal_entry_id,
                'cogs_journal_entry_id' => $order->cogs_journal_entry_id,
            ]);

            return $order->fresh(['lines.menuItem', 'folio']);
        });
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
