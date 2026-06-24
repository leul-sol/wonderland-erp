<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import ApprovalStepper from '../../../Components/ApprovalStepper.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    purchaseOrder: { type: Object, required: true },
    approvalSteps: { type: Array, default: () => [] },
    approvalCurrentStep: { type: String, default: '' },
    canSubmit: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
    canReceive: { type: Boolean, default: false },
});

const submitForm = useForm({});
const approveForm = useForm({});
const receiveForm = useForm({});

const lineColumns = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Item' },
    { key: 'quantity', label: 'Qty' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
    { key: 'line_total', label: 'Line total', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function submitPo() {
    submitForm.post(`/inventory/purchase-orders/${props.purchaseOrder.id}/submit`, { preserveScroll: true });
}

function approve() {
    approveForm.post(`/inventory/purchase-orders/${props.purchaseOrder.id}/approve`, { preserveScroll: true });
}

function receive() {
    receiveForm.post(`/inventory/purchase-orders/${props.purchaseOrder.id}/receive`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`PO ${purchaseOrder.po_number}`">
        <PageHeader
            :title="`PO ${purchaseOrder.po_number}`"
            :subtitle="`${purchaseOrder.vendor_name} · Tier ${purchaseOrder.approval_tier}`"
        >
            <template #actions>
                <StatusBadge :status="purchaseOrder.status" />
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
                <button
                    v-if="canReceive"
                    type="button"
                    class="wh-btn-primary"
                    :disabled="receiveForm.processing"
                    @click="receive"
                >
                    Receive goods
                </button>
            </div>
        </section>

        <section class="wh-card p-4">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Line items</h3>
                <p class="wh-money text-base font-semibold text-slate-900">
                    Total ETB {{ formatMoney(purchaseOrder.total_amount) }}
                </p>
            </div>
            <DataTable :columns="lineColumns" :rows="purchaseOrder.lines ?? []">
                <template #cell-unit_cost="{ row }">
                    <span class="wh-money">{{ formatMoney(row.unit_cost) }}</span>
                </template>
                <template #cell-line_total="{ row }">
                    <span class="wh-money font-medium">{{ formatMoney(row.line_total) }}</span>
                </template>
            </DataTable>
            <p v-if="purchaseOrder.received_at" class="mt-3 text-sm text-emerald-800">
                Received {{ purchaseOrder.received_at }}
            </p>
        </section>
    </AppLayout>
</template>
