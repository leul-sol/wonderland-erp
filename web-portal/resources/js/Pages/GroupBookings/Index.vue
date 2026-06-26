<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    groupBookings: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ tab: 'all' }) },
});

const columns = [
    { key: 'group_code', label: 'Code' },
    { key: 'group_name', label: 'Group' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'room_count', label: 'Rooms' },
    { key: 'status', label: 'Status' },
];

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'checked_in', label: 'In-house' },
    { value: 'checked_out', label: 'Departed' },
];

function applyTab(tab) {
    router.get('/group-bookings', tab === 'all' ? {} : { tab }, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout title="Group bookings">
        <PageHeader title="Group bookings" subtitle="Rooming lists, bulk check-in, and group check-out">
            <template #actions>
                <Link href="/group-bookings/create" class="wh-btn-primary">Create group</Link>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition"
                :class="
                    filters.tab === tab.value
                        ? 'bg-teal-700 text-white ring-teal-700'
                        : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'
                "
                @click="applyTab(tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>

        <DataTable list-title="Group booking list" selectable :columns="columns" :rows="groupBookings" empty-message="No group bookings yet.">
            <template #cell-group_code="{ row }">
                <Link :href="`/group-bookings/${row.id}`" class="wh-table-link">{{ row.group_code }}</Link>
            </template>
            <template #cell-group_name="{ row }">
                <Link :href="`/group-bookings/${row.id}`" class="wh-table-link">{{ row.group_name }}</Link>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </DataTable>
    </AppLayout>
</template>
