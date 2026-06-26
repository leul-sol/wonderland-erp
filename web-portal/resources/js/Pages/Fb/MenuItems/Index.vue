<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    menuItems: { type: Array, default: () => [] },
});

const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Item' },
    { key: 'category', label: 'Category' },
    { key: 'price', label: 'Guest price', class: 'text-right' },
    { key: 'employee_price', label: 'Staff price', class: 'text-right' },
    { key: 'has_recipe', label: 'Recipe' },
    { key: 'is_active', label: 'Available' },
];

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = Number.parseFloat(value);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}
</script>

<template>
    <AppLayout title="Menu items">
        <PageHeader title="Menu items" subtitle="Catalog admin — prices, availability, and recipes">
            <template #actions>
                <Link href="/fb/settings" class="wh-btn-secondary">Catalog admin</Link>
                <Link href="/fb/menu-items/create" class="wh-btn-primary">New item</Link>
            </template>
        </PageHeader>

        <DataTable list-title="All menu items" :columns="columns" :rows="menuItems" empty-message="No menu items found.">
            <template #cell-code="{ row }">
                <Link :href="`/fb/menu-items/${row.id}/edit`" class="wh-table-link">{{ row.code }}</Link>
            </template>
            <template #cell-name="{ row }">
                <Link :href="`/fb/menu-items/${row.id}/edit`" class="wh-table-link">{{ row.name }}</Link>
            </template>
            <template #cell-category="{ row }">
                {{ row.category ?? '—' }}
            </template>
            <template #cell-price="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.price) }}</span>
            </template>
            <template #cell-employee_price="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.employee_price) }}</span>
            </template>
            <template #cell-has_recipe="{ row }">
                {{ row.has_recipe ? 'Yes' : '—' }}
            </template>
            <template #cell-is_active="{ row }">
                <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
            </template>
        </DataTable>
    </AppLayout>
</template>
