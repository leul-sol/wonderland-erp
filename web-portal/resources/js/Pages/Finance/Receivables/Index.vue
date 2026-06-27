<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    status: { type: String, default: 'open' },
    agingBucket: { type: String, default: null },
    receivables: { type: Array, default: () => [] },
    canSettle: { type: Boolean, default: false },
});

const statusTabs = [
    { key: 'open', label: 'Open' },
    { key: 'partial', label: 'Partial' },
    { key: 'settled', label: 'Settled' },
    { key: 'written_off', label: 'Written off' },
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
    { key: 'party_name', label: 'Party' },
    { key: 'source_reference', label: 'Reference' },
    { key: 'status', label: 'Status' },
    { key: 'due_date', label: 'Due' },
    { key: 'aging_bucket', label: 'Aging' },
    { key: 'days_overdue', label: 'Days overdue', class: 'text-right' },
    { key: 'balance', label: 'Balance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const settleForm = useForm({ amount: '', payment_method: 'bank' });
const writeOffForm = useForm({ reason: '' });

function switchTab(tab) {
    router.get('/finance/receivables', {
        status: tab,
        aging_bucket: props.agingBucket || undefined,
    }, { preserveScroll: true });
}

function filterAging(event) {
    const bucket = event.target.value || undefined;
    router.get('/finance/receivables', {
        status: props.status,
        aging_bucket: bucket,
    }, { preserveScroll: true });
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function settle(receivable) {
    settleForm.amount = receivable.balance;
    settleForm.post(`/finance/receivables/${receivable.id}/settle`, { preserveScroll: true });
}

function writeOff(receivable) {
    writeOffForm.post(`/finance/receivables/${receivable.id}/write-off`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Receivables">
        <PageHeader title="Receivables" subtitle="Customer balances with aging buckets">
            <template #actions>
                <Link href="/finance/payables" class="wh-btn-secondary">Payables</Link>
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

        <PageDataSection keys="receivables">
        <DataTable list-title="Receivable list" selectable :columns="columns" :rows="receivables" empty-message="No receivables for this filter.">
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
                <div v-if="canSettle && ['open', 'partial'].includes(row.status)" class="flex flex-wrap items-end justify-end gap-2">
                    <input v-model="settleForm.amount" type="number" step="0.01" class="wh-input w-28" />
                    <select v-model="settleForm.payment_method" class="wh-input w-24">
                        <option value="bank">Bank</option>
                        <option value="cash">Cash</option>
                        <option value="pos">POS</option>
                        <option value="visa">Visa</option>
                    </select>
                    <button type="button" class="wh-btn-primary text-xs" @click="settle(row)">Settle</button>
                    <button type="button" class="wh-btn-secondary text-xs" @click="writeOff(row)">Write off</button>
                </div>
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
