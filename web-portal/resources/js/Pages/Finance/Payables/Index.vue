<script setup>
import { useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    payables: { type: Array, default: () => [] },
});

const columns = [
    { key: 'vendor_name', label: 'Vendor' },
    { key: 'source_reference', label: 'Reference' },
    { key: 'status', label: 'Status' },
    { key: 'balance', label: 'Balance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const settleForm = useForm({
    amount: '',
    payment_method: 'bank',
});

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function settle(payable) {
    settleForm.amount = payable.balance;
    settleForm.post(`/finance/payables/${payable.id}/settle`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Open payables">
        <PageHeader title="Open payables" subtitle="Settle vendor balances from goods receipt (AP)">
            <template #actions>
                <StatusBadge status="open" />
            </template>
        </PageHeader>

        <PageDataSection keys="payables">
        <DataTable list-title="Payable list" selectable :columns="columns" :rows="payables" empty-message="No open payables.">
            <template #cell-balance="{ row }">
                <span class="wh-money font-semibold">ETB {{ formatMoney(row.balance) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex flex-col items-end gap-2 sm:flex-row sm:items-end">
                    <MoneyField
                        :id="`amount-${row.id}`"
                        v-model="settleForm.amount"
                        label="Pay amount"
                    />
                    <div>
                        <label :for="`method-${row.id}`" class="mb-1 block text-xs font-medium text-slate-600">Method</label>
                        <select :id="`method-${row.id}`" v-model="settleForm.payment_method" class="wh-input w-28">
                            <option value="bank">Bank</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                    <button
                        type="button"
                        class="wh-btn-primary text-xs"
                        :disabled="settleForm.processing"
                        @click="settle(row)"
                    >
                        Settle
                    </button>
                </div>
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
