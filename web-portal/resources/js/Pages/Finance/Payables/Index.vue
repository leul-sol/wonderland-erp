<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    status: { type: String, default: 'open' },
    agingBucket: { type: String, default: null },
    payables: { type: Array, default: () => [] },
});

const statusTabs = [
    { key: 'open', label: 'Open' },
    { key: 'partial', label: 'Partial' },
    { key: 'settled', label: 'Settled' },
];

const agingOptions = [
    { key: '', label: 'All buckets' },
    { key: 'current', label: 'Current' },
    { key: '1_30', label: '1–30 days' },
    { key: '31_60', label: '31–60 days' },
    { key: '61_90', label: '61–90 days' },
    { key: '90_plus', label: '90+ days' },
];

const columns = [
    { key: 'vendor_name', label: 'Vendor' },
    { key: 'source_reference', label: 'Reference' },
    { key: 'status', label: 'Status' },
    { key: 'due_date', label: 'Due' },
    { key: 'aging_bucket', label: 'Aging' },
    { key: 'days_overdue', label: 'Days overdue', class: 'text-right' },
    { key: 'balance', label: 'Balance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const settleForm = useForm({
    amount: '',
    payment_method: 'bank',
});

function switchTab(tab) {
    router.get('/finance/payables', {
        status: tab,
        aging_bucket: props.agingBucket || undefined,
    }, { preserveScroll: true });
}

function filterAging(event) {
    const bucket = event.target.value || undefined;
    router.get('/finance/payables', {
        status: props.status,
        aging_bucket: bucket,
    }, { preserveScroll: true });
}

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
    <AppLayout title="Payables">
        <PageHeader title="Payables" subtitle="Vendor balances with aging buckets">
            <template #actions>
                <Link href="/finance/receivables" class="wh-btn-secondary">Receivables</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in statusTabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    :class="status === tab.key ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700'"
                    @click="switchTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>
            <div class="mt-4">
                <select class="wh-input w-48" :value="agingBucket ?? ''" @change="filterAging">
                    <option v-for="option in agingOptions" :key="option.key" :value="option.key">{{ option.label }}</option>
                </select>
            </div>
        </section>

        <PageDataSection keys="payables">
        <DataTable list-title="Payable list" selectable :columns="columns" :rows="payables" empty-message="No payables for this filter.">
            <template #cell-balance="{ row }">
                <span class="wh-money font-semibold">ETB {{ formatMoney(row.balance) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-aging_bucket="{ row }">
                <span class="text-xs capitalize">{{ String(row.aging_bucket ?? '').replaceAll('_', ' ') }}</span>
            </template>
            <template #cell-actions="{ row }">
                <div v-if="['open', 'partial'].includes(row.status)" class="flex flex-col items-end gap-2 sm:flex-row sm:items-end">
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
