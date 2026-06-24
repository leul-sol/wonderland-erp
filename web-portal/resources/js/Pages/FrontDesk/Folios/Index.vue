<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    folios: { type: Array, default: () => [] },
});

const columns = [
    { key: 'id', label: 'Folio #' },
    { key: 'reservation_id', label: 'Reservation' },
    { key: 'status', label: 'Status' },
    { key: 'balance', label: 'Balance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Open folios">
        <PageHeader title="Open folios" subtitle="Select a folio to post charges, settle, and check out">
            <template #actions>
                <Link href="/front-desk/check-in" class="wh-btn-primary">Check in guest</Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="folios" empty-message="No open folios.">
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-balance="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.balance) }}</span>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/front-desk/folios/${row.id}`" class="wh-btn-secondary text-xs">Open folio</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
