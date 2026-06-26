<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    orders: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ tab: 'open' }) },
});

const columns = [
    { key: 'order_number', label: 'Order #' },
    { key: 'customer_type', label: 'Customer' },
    { key: 'dining_table', label: 'Table' },
    { key: 'total_amount', label: 'Total', class: 'text-right' },
    { key: 'status', label: 'Status' },
    { key: 'bill_status', label: 'Bill' },
];

const tabs = [
    { value: 'open', label: 'Open' },
    { value: 'finalized', label: 'Awaiting payment' },
    { value: 'billed', label: 'Paid / posted' },
    { value: 'all', label: 'All' },
];

function applyTab(tab) {
    router.get('/fb/orders', tab === 'open' ? { tab } : { tab }, { preserveState: true, replace: true });
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function customerLabel(type) {
    return ({
        hotel_guest: 'Hotel guest',
        outside_cash: 'Walk-in cash',
        outside_credit: 'Walk-in credit',
        event: 'Event',
        employee: 'Staff meal',
    })[type] ?? type;
}
</script>

<template>
    <AppLayout title="F&B orders">
        <PageHeader title="Restaurant orders" subtitle="Open tabs, finalize bills, and post to folios">
            <template #actions>
                <Link href="/fb/menu" class="wh-btn-secondary">Menu</Link>
                <Link href="/fb/orders/create" class="wh-btn-primary">New order</Link>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition"
                :class="
                    filters.tab === tab.value
                        ? 'bg-teal-700 text-white ring-teal-700'
                        : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'
                "
                @click="applyTab(tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>

        <DataTable list-title="Order queue" :columns="columns" :rows="orders" empty-message="No orders in this queue.">
            <template #cell-order_number="{ row }">
                <Link :href="`/fb/orders/${row.id}`" class="wh-table-link">{{ row.order_number }}</Link>
            </template>
            <template #cell-customer_type="{ row }">
                {{ customerLabel(row.customer_type) }}
            </template>
            <template #cell-dining_table="{ row }">
                {{ row.dining_table?.table_number ?? '—' }}
            </template>
            <template #cell-total_amount="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_amount) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status === 'open' ? 'draft' : row.status" />
            </template>
            <template #cell-bill_status="{ row }">
                <StatusBadge v-if="row.bill" :status="row.bill.status" />
                <span v-else class="text-slate-400">—</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
