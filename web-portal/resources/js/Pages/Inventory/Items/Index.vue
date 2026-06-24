<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    items: { type: Array, default: () => [] },
});

const columns = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Item' },
    { key: 'unit', label: 'Unit' },
    { key: 'quantity_on_hand', label: 'On hand', class: 'text-right' },
    { key: 'reorder_level', label: 'Reorder', class: 'text-right' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
];
</script>

<template>
    <AppLayout title="Inventory items">
        <PageHeader title="Inventory items" subtitle="Stock on hand and reorder levels">
            <template #actions>
                <Link href="/inventory/purchase-orders" class="wh-btn-secondary">Purchase orders</Link>
                <Link href="/inventory/purchase-orders/create" class="wh-btn-primary">Create PO</Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="items" empty-message="No inventory items found.">
            <template #cell-quantity_on_hand="{ row }">
                <span class="font-mono tabular-nums">{{ row.quantity_on_hand }}</span>
            </template>
            <template #cell-reorder_level="{ row }">
                <span class="font-mono tabular-nums">{{ row.reorder_level }}</span>
            </template>
            <template #cell-unit_cost="{ row }">
                <span class="wh-money">{{ row.unit_cost }}</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
