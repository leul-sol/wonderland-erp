<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    menuItems: { type: Array, default: () => [] },
});

const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Item' },
    { key: 'category', label: 'Category' },
    { key: 'price', label: 'Price', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Menu">
        <PageHeader title="Restaurant menu" subtitle="Active items available for folio and cashier orders">
            <template #actions>
                <Link href="/fb/settings" class="wh-btn-secondary">Catalog admin</Link>
            </template>
        </PageHeader>

        <DataTable list-title="Menu item list" selectable :columns="columns" :rows="menuItems" empty-message="No menu items found.">
            <template #cell-category="{ row }">
                {{ row.category ?? '—' }}
            </template>
            <template #cell-price="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.price) }}</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
