<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: '' }) },
});

const columns = [
    { key: 'room_number', label: 'Room' },
    { key: 'floor', label: 'Floor' },
    { key: 'room_type', label: 'Type' },
    { key: 'status', label: 'Status' },
];

const statusFilters = [
    { value: '', label: 'All' },
    { value: 'available', label: 'Available' },
    { value: 'occupied', label: 'Occupied' },
    { value: 'maintenance', label: 'Maintenance' },
];

function applyFilter(status) {
    router.get('/front-desk/rooms', status ? { status } : {}, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout title="Room status">
        <PageHeader title="Rooms" subtitle="Live room status across the property">
            <template #actions>
                <Link href="/front-desk/check-in" class="wh-btn-primary">Check in guest</Link>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="filter in statusFilters"
                :key="filter.value"
                type="button"
                class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition"
                :class="
                    filters.status === filter.value
                        ? 'bg-teal-700 text-white ring-teal-700'
                        : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'
                "
                @click="applyFilter(filter.value)"
            >
                {{ filter.label }}
            </button>
        </div>

        <DataTable :columns="columns" :rows="rooms" empty-message="No rooms match this filter.">
            <template #cell-room_type="{ row }">
                {{ row.room_type?.name ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </DataTable>
    </AppLayout>
</template>
