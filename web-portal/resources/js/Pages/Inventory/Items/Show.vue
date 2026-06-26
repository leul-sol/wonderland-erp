<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    item: { type: Object, required: true },
    stock: { type: Object, default: () => ({}) },
    movements: { type: Array, default: () => [] },
});

const batchColumns = [
    { key: 'batch_code', label: 'Batch' },
    { key: 'quantity_remaining', label: 'Remaining', class: 'text-right' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
    { key: 'received_date', label: 'Received' },
    { key: 'expiry_date', label: 'Expiry' },
];

const movementColumns = [
    { key: 'movement_type', label: 'Type' },
    { key: 'quantity', label: 'Qty', class: 'text-right' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
    { key: 'reference_type', label: 'Reference' },
    { key: 'created_at', label: 'When' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function isLowStock(item) {
    const onHand = Number.parseFloat(item.quantity_on_hand ?? 0);
    const reorder = Number.parseFloat(item.reorder_level ?? 0);
    return Number.isFinite(onHand) && Number.isFinite(reorder) && onHand <= reorder;
}
</script>

<template>
    <AppLayout :title="item.name">
        <PageHeader :title="item.name" :subtitle="`${item.sku} · ${item.unit ?? 'unit'}`">
            <template #actions>
                <StatusBadge v-if="isLowStock(item)" status="open" />
                <Link href="/inventory/items" class="wh-btn-secondary text-xs">All items</Link>
                <Link href="/inventory/alerts" class="wh-btn-secondary text-xs">Alerts</Link>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">On hand</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ stock.current_stock ?? item.quantity_on_hand }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Reorder level</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ item.reorder_level }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Unit cost</p>
                <p class="wh-money mt-1 text-2xl font-semibold text-teal-800">ETB {{ formatMoney(item.unit_cost) }}</p>
            </div>
        </div>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Active batches</h3>
            <DataTable :columns="batchColumns" :rows="stock.batches ?? []" empty-message="No active stock batches.">
                <template #cell-unit_cost="{ row }">
                    <span class="wh-money">{{ formatMoney(row.unit_cost) }}</span>
                </template>
                <template #cell-expiry_date="{ row }">
                    {{ row.expiry_date ?? '—' }}
                </template>
            </DataTable>
        </section>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Recent movements</h3>
            <DataTable :columns="movementColumns" :rows="movements" empty-message="No stock movements recorded.">
                <template #cell-movement_type="{ row }">
                    <StatusBadge :status="row.movement_type" />
                </template>
                <template #cell-quantity="{ row }">
                    <span class="font-mono tabular-nums">{{ row.quantity }}</span>
                </template>
                <template #cell-unit_cost="{ row }">
                    <span class="wh-money">{{ formatMoney(row.unit_cost) }}</span>
                </template>
                <template #cell-created_at="{ row }">
                    {{ row.created_at ? new Date(row.created_at).toLocaleString() : '—' }}
                </template>
            </DataTable>
        </section>
    </AppLayout>
</template>
