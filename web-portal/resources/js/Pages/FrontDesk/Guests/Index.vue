<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    guests: { type: Array, default: () => [] },
});

const columns = [
    { key: 'full_name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'nationality', label: 'Nationality' },
    { key: 'actions', label: '', class: 'text-right' },
];
</script>

<template>
    <AppLayout title="Guest profiles">
        <PageHeader title="Guest profiles" subtitle="Registered guests for reservations and folios">
            <template #actions>
                <Link href="/front-desk/guests/create" class="wh-btn-primary">Add guest</Link>
                <Link href="/front-desk/check-in?from=guests" class="wh-btn-secondary">Check in</Link>
            </template>
        </PageHeader>

        <DataTable list-title="Guests" :columns="columns" :rows="guests" empty-message="No guest profiles yet.">
            <template #cell-full_name="{ row }">
                <Link :href="`/front-desk/guests/${row.id}/edit`" class="wh-table-link">{{ row.full_name }}</Link>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/front-desk/check-in?guest_id=${row.id}`" class="text-xs font-medium text-teal-700 hover:text-teal-900">
                    Check in
                </Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
