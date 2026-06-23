<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseOrderService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly S4FinanceClient $s4,
        private readonly OutboxService $outbox,
    ) {
    }

    /**
     * @param  array<int, array{inventory_item_id: int, quantity: float|int, unit_cost: float|int}>  $lines
     */
    public function create(string $vendorName, array $lines): PurchaseOrder
    {
        if ($lines === []) {
            throw new InvalidArgumentException('Purchase order requires at least one line.');
        }

        return DB::transaction(function () use ($vendorName, $lines) {
            $po = PurchaseOrder::query()->create([
                'po_number' => $this->nextPoNumber(),
                'vendor_name' => $vendorName,
                'status' => 'draft',
                'total_amount' => 0,
                'approval_tier' => 1,
            ]);

            $total = 0.0;

            foreach ($lines as $line) {
                $item = InventoryItem::query()->findOrFail((int) $line['inventory_item_id']);
                $quantity = round((float) $line['quantity'], 3);
                $unitCost = round((float) $line['unit_cost'], 2);
                $lineTotal = round($quantity * $unitCost, 2);
                $total += $lineTotal;

                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $po->id,
                    'inventory_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);
            }

            $total = round($total, 2);
            $po->update([
                'total_amount' => $total,
                'approval_tier' => $this->requiredApprovalTier($total),
            ]);

            return $po->fresh('lines.inventoryItem');
        });
    }

    public function submit(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new InvalidArgumentException('Only draft purchase orders can be submitted.');
        }

        $po->update([
            'status' => 'pending_dept_head',
            'approval_tier' => $this->requiredApprovalTier((float) $po->total_amount),
        ]);

        return $po->fresh('lines.inventoryItem');
    }

    /**
     * @param  list<string>  $roles
     */
    public function approve(PurchaseOrder $po, int $approvedBy, array $roles): PurchaseOrder
    {
        if ($po->status === 'draft') {
            $po = $this->submit($po);
        }

        if (! in_array($po->status, ['pending_dept_head', 'pending_finance', 'pending_gm'], true)) {
            throw new InvalidArgumentException('Purchase order is not awaiting approval.');
        }

        $isSuperAdmin = in_array('super_admin', $roles, true);

        do {
            if (! $this->canApproveCurrentStep($po->status, $roles)) {
                throw new InvalidArgumentException('Insufficient role for current approval step.');
            }

            $po = $this->advanceApprovalStep($po, $approvedBy);
        } while ($isSuperAdmin && in_array($po->status, ['pending_dept_head', 'pending_finance', 'pending_gm'], true));

        if ($po->status === 'approved') {
            $this->outbox->enqueue(config('events.channels.purchase_order_approved'), [
                'purchase_order_id' => $po->id,
                'po_number' => $po->po_number,
                'vendor_name' => $po->vendor_name,
                'total_amount' => (string) $po->total_amount,
                'approval_tier' => $po->approval_tier,
            ]);
        }

        return $po->fresh('lines.inventoryItem');
    }

    public function receive(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'approved') {
            throw new InvalidArgumentException('Only approved purchase orders can be received.');
        }

        $po->loadMissing('lines.inventoryItem');
        $accounts = config('hospitality.accounts');

        return DB::transaction(function () use ($po, $accounts) {
            foreach ($po->lines as $line) {
                $this->inventory->receiveStock(
                    $line->inventory_item_id,
                    (float) $line->quantity,
                    (float) $line->unit_cost,
                    'purchase_order',
                    $po->id
                );
            }

            $journal = $this->s4->postJournal([
                'description' => 'Goods received '.$po->po_number,
                'source_module' => 's3',
                'source_reference' => 'PO-'.$po->id,
                'lines' => [
                    ['account_code' => $accounts['inventory_fb'], 'debit' => (float) $po->total_amount, 'credit' => 0],
                    ['account_code' => $accounts['ap_suppliers'], 'debit' => 0, 'credit' => (float) $po->total_amount],
                ],
            ], 'po-'.$po->id.'-receive');

            $po->update([
                'status' => 'received',
                'received_at' => now(),
                's4_journal_entry_id' => (string) ($journal['data']['id'] ?? ''),
            ]);

            $this->outbox->enqueue(config('events.channels.goods_received'), [
                'purchase_order_id' => $po->id,
                'po_number' => $po->po_number,
                'total_amount' => (string) $po->total_amount,
                'journal_entry_id' => $po->s4_journal_entry_id,
            ]);

            return $po->fresh('lines.inventoryItem');
        });
    }

    private function requiredApprovalTier(float $totalAmount): int
    {
        $financeThreshold = (float) config('hospitality.po_finance_threshold', 50000);
        $deptThreshold = (float) config('hospitality.po_dept_head_threshold', 5000);

        if ($totalAmount >= $financeThreshold) {
            return 3;
        }

        if ($totalAmount >= $deptThreshold) {
            return 2;
        }

        return 1;
    }

    /**
     * @param  list<string>  $roles
     */
    private function canApproveCurrentStep(string $status, array $roles): bool
    {
        if (in_array('super_admin', $roles, true)) {
            return true;
        }

        return match ($status) {
            'pending_dept_head' => $this->hasAnyRole($roles, ['department_head', 'general_manager']),
            'pending_finance' => $this->hasAnyRole($roles, ['finance_manager']),
            'pending_gm' => $this->hasAnyRole($roles, ['general_manager']),
            default => false,
        };
    }

    /**
     * @param  list<string>  $roles
     */
    private function hasAnyRole(array $roles, array $allowed): bool
    {
        foreach ($allowed as $role) {
            if (in_array($role, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    private function advanceApprovalStep(PurchaseOrder $po, int $approvedBy): PurchaseOrder
    {
        $tier = (int) $po->approval_tier;

        $nextStatus = match ($po->status) {
            'pending_dept_head' => $tier === 1 ? 'approved' : 'pending_finance',
            'pending_finance' => $tier === 2 ? 'approved' : 'pending_gm',
            'pending_gm' => 'approved',
            default => throw new InvalidArgumentException('Purchase order is not awaiting approval.'),
        };

        $po->update([
            'status' => $nextStatus,
            'approved_by' => $approvedBy,
            'approved_at' => $nextStatus === 'approved' ? now() : $po->approved_at,
        ]);

        return $po;
    }

    private function nextPoNumber(): string
    {
        $last = PurchaseOrder::query()->orderByDesc('id')->lockForUpdate()->value('po_number');
        $sequence = 1;

        if (is_string($last) && preg_match('/PO-(\d+)/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'PO-'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
