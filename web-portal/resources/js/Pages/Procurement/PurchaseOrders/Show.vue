<script setup>
import { useForm } from '@inertiajs/vue3';
import ApprovalStepper from '../../../Components/ApprovalStepper.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    purchaseOrder: { type: Object, required: true },
    approvalSteps: { type: Array, default: () => [] },
    approvalCurrentStep: { type: String, default: '' },
    canApprove: { type: Boolean, default: false },
});

const approveForm = useForm({});

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

function approve() {
    approveForm.post(`/procurement/purchase-orders/${props.purchaseOrder.id}/approve`, {
        preserveScroll: true,
    });
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
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Approval workflow</h3>
            <ApprovalStepper :steps="approvalSteps" :current-key="approvalCurrentStep" />
            <div v-if="canApprove" class="mt-4 flex justify-end">
                <button type="button" class="wh-btn-primary" :disabled="approveForm.processing" @click="approve">
                    Approve current step
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
        </section>
    </AppLayout>
</template>
