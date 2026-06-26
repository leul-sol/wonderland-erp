<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    lowStockAlerts: { type: Array, default: () => [] },
    expiryAlerts: { type: Array, default: () => [] },
});

const lowStockColumns = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Item' },
    { key: 'quantity_on_hand', label: 'On hand', class: 'text-right' },
    { key: 'reorder_level', label: 'Reorder', class: 'text-right' },
];

const expiryColumns = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Item' },
    { key: 'batch_code', label: 'Batch' },
    { key: 'quantity_remaining', label: 'Qty', class: 'text-right' },
    { key: 'expiry_date', label: 'Expires' },
];
</script>

<template>
    <AppLayout title="Stock alerts">
        <PageHeader title="Stock alerts" subtitle="Low stock and approaching expiry (read-only)">
            <template #actions>
                <Link href="/inventory/items" class="wh-btn-secondary">Items</Link>
                <Link href="/inventory/valuation" class="wh-btn-secondary">Valuation</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-amber-800">
                Low stock ({{ lowStockAlerts.length }})
            </h3>
            <DataTable
                :columns="lowStockColumns"
                :rows="lowStockAlerts"
                empty-message="All items are above reorder level."
            >
                <template #cell-sku="{ row }">
                    <Link
                        v-if="row.id"
                        :href="`/inventory/items/${row.id}`"
                        class="wh-table-link"
                    >
                        {{ row.sku }}
                    </Link>
                    <span v-else>{{ row.sku }}</span>
                </template>
            </DataTable>
        </section>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-rose-800">
                Expiry alerts ({{ expiryAlerts.length }})
            </h3>
            <DataTable
                :columns="expiryColumns"
                :rows="expiryAlerts"
                empty-message="No batches nearing expiry."
            >
                <template #cell-sku="{ row }">
                    <Link
                        v-if="row.inventory_item_id"
                        :href="`/inventory/items/${row.inventory_item_id}`"
                        class="wh-table-link"
                    >
                        {{ row.sku }}
                    </Link>
                    <span v-else>{{ row.sku }}</span>
                </template>
            </DataTable>
        </section>
    </AppLayout>
</template>
