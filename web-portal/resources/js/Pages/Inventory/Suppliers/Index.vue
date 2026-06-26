<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    suppliers: { type: Array, default: () => [] },
});

const columns = [
    { key: 'name', label: 'Supplier' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'phone', label: 'Phone' },
    { key: 'payment_terms', label: 'Terms' },
    { key: 'outstanding_balance', label: 'Outstanding', class: 'text-right' },
];
</script>

<template>
    <AppLayout title="Suppliers">
        <PageHeader title="Suppliers" subtitle="Vendor master for procurement" />

        <DataTable list-title="Supplier list" selectable :columns="columns" :rows="suppliers" empty-message="No suppliers found.">
            <template #cell-name="{ row }">
                <Link :href="`/inventory/suppliers/${row.id}`" class="wh-table-link">{{ row.name }}</Link>
            </template>
            <template #cell-outstanding_balance="{ row }">
                <span class="wh-money">ETB {{ row.outstanding_balance ?? '0.00' }}</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
