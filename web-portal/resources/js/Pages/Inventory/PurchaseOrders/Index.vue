<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    purchaseOrders: { type: Array, default: () => [] },
});

const columns = [
    { key: 'po_number', label: 'PO #' },
    { key: 'vendor_name', label: 'Vendor' },
    { key: 'status', label: 'Status' },
    { key: 'approval_tier', label: 'Tier' },
    { key: 'total_amount', label: 'Total', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Purchase orders">
        <PageHeader title="Purchase orders" subtitle="Create, approve, and receive goods">
            <template #actions>
                <Link href="/inventory/items" class="wh-btn-secondary">Inventory</Link>
                <Link href="/inventory/purchase-orders/create" class="wh-btn-primary">Create PO</Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="purchaseOrders" empty-message="No purchase orders found.">
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-total_amount="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_amount) }}</span>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/inventory/purchase-orders/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
