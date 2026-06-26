<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApprovalStepper from '../../../Components/ApprovalStepper.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    purchaseOrder: { type: Object, required: true },
    approvalSteps: { type: Array, default: () => [] },
    approvalCurrentStep: { type: String, default: '' },
    approvalTierLabel: { type: String, default: '' },
    canSubmit: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
    canReceive: { type: Boolean, default: false },
});

const submitForm = useForm({});
const approveForm = useForm({});

function remainingQty(line) {
    const ordered = Number.parseFloat(line.quantity ?? 0);
    const received = Number.parseFloat(line.quantity_received ?? 0);
    return Math.max(0, ordered - received);
}

const receivableLines = computed(() =>
    (props.purchaseOrder.lines ?? []).filter((line) => remainingQty(line) > 0),
);

const receiveForm = useForm({
    lines: receivableLines.value.map((line) => ({
        purchase_order_line_id: line.id,
        quantity_received: remainingQty(line),
    })),
});

const lineColumns = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Item' },
    { key: 'quantity', label: 'Ordered' },
    { key: 'quantity_received', label: 'Received' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
    { key: 'line_total', label: 'Line total', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function receiveLineIndex(lineId) {
    return receiveForm.lines.findIndex((line) => line.purchase_order_line_id === lineId);
}

function receiveQtyForLine(lineId) {
    const index = receiveLineIndex(lineId);
    return index >= 0 ? receiveForm.lines[index].quantity_received : 0;
}

function setReceiveQty(lineId, value) {
    const index = receiveLineIndex(lineId);
    if (index >= 0) {
        receiveForm.lines[index].quantity_received = value;
    }
}

async function submitPo() {
    const ok = await confirmAction({
        title: 'Submit purchase order',
        message: `Submit PO ${props.purchaseOrder.po_number} for approval? (${props.approvalTierLabel})`,
        confirmLabel: 'Submit',
    });

    if (!ok) {
        return;
    }

    submitForm.post(`/inventory/purchase-orders/${props.purchaseOrder.id}/submit`, { preserveScroll: true });
}

async function approve() {
    const ok = await confirmAction({
        title: 'Approve purchase order',
        message: `Record your approval for PO ${props.purchaseOrder.po_number}?`,
        confirmLabel: 'Approve',
    });

    if (!ok) {
        return;
    }

    approveForm.post(`/inventory/purchase-orders/${props.purchaseOrder.id}/approve`, { preserveScroll: true });
}

async function receiveGoods() {
    const lines = receiveForm.lines.filter((line) => Number.parseFloat(line.quantity_received) > 0);

    if (lines.length === 0) {
        return;
    }

    const ok = await confirmAction({
        title: 'Receive goods',
        message: `Post goods receipt for ${lines.length} line(s) on PO ${props.purchaseOrder.po_number}?`,
        confirmLabel: 'Receive goods',
    });

    if (!ok) {
        return;
    }

    receiveForm
        .transform(() => ({ lines }))
        .post(`/inventory/purchase-orders/${props.purchaseOrder.id}/receive`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`PO ${purchaseOrder.po_number}`">
        <PageHeader
            :title="`PO ${purchaseOrder.po_number}`"
            :subtitle="`${purchaseOrder.vendor_name} · ${approvalTierLabel}`"
        >
            <template #actions>
                <StatusBadge :status="purchaseOrder.status" />
                <Link href="/inventory/purchase-orders" class="wh-btn-secondary text-xs">All POs</Link>
                <Link href="/finance/payables" class="wh-btn-secondary text-xs">Payables</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Approval workflow</h3>
            <ApprovalStepper :steps="approvalSteps" :current-key="approvalCurrentStep" />
            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button
                    v-if="canSubmit"
                    type="button"
                    class="wh-btn-secondary"
                    :disabled="submitForm.processing"
                    @click="submitPo"
                >
                    Submit for approval
                </button>
                <button
                    v-if="canApprove"
                    type="button"
                    class="wh-btn-primary"
                    :disabled="approveForm.processing"
                    @click="approve"
                >
                    Approve current step
                </button>
            </div>
        </section>

        <section class="wh-card mb-6 p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Line items</h3>
                <p class="wh-money text-base font-semibold text-slate-900">
                    Total ETB {{ formatMoney(purchaseOrder.total_amount) }}
                </p>
            </div>
            <DataTable :columns="lineColumns" :rows="purchaseOrder.lines ?? []">
                <template #cell-quantity_received="{ row }">
                    <span class="font-mono tabular-nums">{{ row.quantity_received ?? '0' }}</span>
                </template>
                <template #cell-unit_cost="{ row }">
                    <span class="wh-money">{{ formatMoney(row.unit_cost) }}</span>
                </template>
                <template #cell-line_total="{ row }">
                    <span class="wh-money font-medium">{{ formatMoney(row.line_total) }}</span>
                </template>
            </DataTable>
            <p v-if="purchaseOrder.received_at" class="mt-3 text-sm text-emerald-800">
                Fully received {{ purchaseOrder.received_at }}
            </p>
        </section>

        <section v-if="canReceive && receivableLines.length > 0" class="wh-card p-4">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Goods receipt</h3>
            <p class="mb-4 text-sm text-slate-600">
                Enter quantities received this delivery. Partial receipts are supported.
            </p>
            <form @submit.prevent="receiveGoods">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-2 py-2">Item</th>
                                <th class="px-2 py-2 text-right">Remaining</th>
                                <th class="px-2 py-2 text-right">Receive now</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in receivableLines"
                                :key="line.id"
                                class="border-b border-slate-100"
                            >
                                <td class="px-2 py-2">
                                    <span class="font-medium">{{ line.name }}</span>
                                    <span class="ml-2 text-xs text-slate-500">{{ line.sku }}</span>
                                </td>
                                <td class="px-2 py-2 text-right font-mono tabular-nums">
                                    {{ remainingQty(line) }}
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <input
                                        type="number"
                                        min="0"
                                        :max="remainingQty(line)"
                                        step="0.001"
                                        class="wh-input w-28 text-right"
                                        :value="receiveQtyForLine(line.id)"
                                        @input="setReceiveQty(line.id, $event.target.value)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="wh-btn-primary" :disabled="receiveForm.processing">
                        Receive goods
                    </button>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
