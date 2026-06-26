<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    reservations: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: '' }) },
});

const columns = [
    { key: 'confirmation_code', label: 'Confirmation' },
    { key: 'guest_name', label: 'Guest' },
    { key: 'check_in_date', label: 'Check-in' },
    { key: 'check_out_date', label: 'Check-out' },
    { key: 'room', label: 'Room' },
    { key: 'status', label: 'Status' },
];

const statusFilters = [
    { value: '', label: 'All' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'checked_in', label: 'Checked in' },
    { value: 'checked_out', label: 'Checked out' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'no_show', label: 'No-show' },
];

function applyFilter(status) {
    router.get('/front-desk/reservations', status ? { status } : {}, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout title="Reservations">
        <PageHeader title="Reservations" subtitle="Arrivals, in-house guests, and history">
            <template #actions>
                <Link href="/front-desk/reservations/create" class="wh-btn-secondary">Book reservation</Link>
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

        <DataTable list-title="Reservation list" :columns="columns" :rows="reservations" empty-message="No reservations match this filter.">
            <template #cell-confirmation_code="{ row }">
                <Link :href="`/front-desk/reservations/${row.id}`" class="wh-table-link">{{ row.confirmation_code }}</Link>
            </template>
            <template #cell-room="{ row }">
                {{ row.room?.room_number ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </DataTable>
    </AppLayout>
</template>
