<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    receivables: { type: Array, default: () => [] },
    canSettle: { type: Boolean, default: false },
});

const columns = [
    { key: 'party_name', label: 'Party' },
    { key: 'source_reference', label: 'Reference' },
    { key: 'status', label: 'Status' },
    { key: 'balance', label: 'Balance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const settleForm = useForm({ amount: '', payment_method: 'bank' });
const writeOffForm = useForm({ reason: '' });

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
        <PageHeader title="Receivables" subtitle="Settle customer balances or write off uncollectible amounts">
            <template #actions>
                <Link href="/finance/payables" class="wh-btn-secondary">Payables</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="receivables">
        <DataTable list-title="Receivable list" selectable :columns="columns" :rows="receivables" empty-message="No open receivables.">
            <template #cell-balance="{ row }">
                <span class="wh-money font-semibold">ETB {{ formatMoney(row.balance) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <div v-if="canSettle" class="flex flex-wrap items-end justify-end gap-2">
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
