<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    groupBookings: { type: Array, default: () => [] },
});

const columns = [
    { key: 'group_code', label: 'Code' },
    { key: 'group_name', label: 'Group' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'room_count', label: 'Rooms' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];
</script>

<template>
    <AppLayout title="Group bookings">
        <PageHeader title="Group bookings" subtitle="Rooming lists, bulk check-in, and group check-out">
            <template #actions>
                <Link href="/group-bookings/create" class="wh-btn-primary">Create group</Link>
            </template>
        </PageHeader>

        <DataTable list-title="Group booking list" selectable :columns="columns" :rows="groupBookings" empty-message="No group bookings yet.">
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/group-bookings/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
