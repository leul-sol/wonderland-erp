<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    totalValue: { type: String, default: '0' },
    lines: { type: Array, default: () => [] },
});

const columns = [
    { key: 'sku', label: 'SKU' },
    { key: 'batch_id', label: 'Batch' },
    { key: 'quantity', label: 'Qty', class: 'text-right' },
    { key: 'value', label: 'Value (ETB)', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Inventory valuation">
        <PageHeader title="Inventory valuation" subtitle="FIFO/FEFO batch valuation (read-only)">
            <template #actions>
                <Link href="/inventory/items" class="wh-btn-secondary">Items</Link>
                <Link href="/inventory/alerts" class="wh-btn-secondary">Alerts</Link>
            </template>
        </PageHeader>

        <div class="wh-card mb-6 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Total inventory value</p>
            <p class="wh-money mt-1 text-3xl font-semibold text-teal-800">ETB {{ formatMoney(totalValue) }}</p>
        </div>

        <DataTable list-title="Valuation by batch" :columns="columns" :rows="lines" empty-message="No active stock batches.">
            <template #cell-sku="{ row }">
                <Link
                    v-if="row.item_id"
                    :href="`/inventory/items/${row.item_id}`"
                    class="wh-table-link"
                >
                    {{ row.sku }}
                </Link>
                <span v-else>{{ row.sku }}</span>
            </template>
            <template #cell-quantity="{ row }">
                <span class="font-mono tabular-nums">{{ row.quantity }}</span>
            </template>
            <template #cell-value="{ row }">
                <span class="wh-money font-medium">{{ formatMoney(row.value) }}</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
