<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import CheckInModal from '../../../Components/FrontDesk/CheckInModal.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import { useCheckInModal } from '../../../composables/useCheckInModal';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: '' }) },
    canUpdateStatus: { type: Boolean, default: false },
    checkInLoad: { type: Object, default: null },
    checkInGuestId: { type: Number, default: null },
});

const { showCheckInModal, checkInGuestId, openCheckInModal, closeCheckInModal, canCheckInGuest } = useCheckInModal(
    props.checkInGuestId,
);

const statusForm = useForm({ status: 'available' });

const columns = [
    { key: 'room_number', label: 'Room' },
    { key: 'floor', label: 'Floor' },
    { key: 'room_type', label: 'Type' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const statusFilters = [
    { value: '', label: 'All' },
    { value: 'available', label: 'Available' },
    { value: 'occupied', label: 'Occupied' },
    { value: 'cleaning', label: 'Cleaning' },
    { value: 'maintenance', label: 'Maintenance' },
];

function applyFilter(status) {
    router.get('/front-desk/rooms', status ? { status } : {}, { preserveState: true, replace: true });
}

async function setRoomStatus(roomId, status, label) {
    const ok = await confirmAction({
        title: 'Update room status',
        message: `Set room to ${label}?`,
        confirmLabel: 'Update status',
    });

    if (!ok) {
        return;
    }

    statusForm.status = status;
    statusForm.put(`/front-desk/rooms/${roomId}/status`, { preserveScroll: true });
}

function canChangeStatus(row) {
    return props.canUpdateStatus && row.status !== 'occupied';
}
</script>

<template>
    <AppLayout title="Room status">
        <PageHeader title="Rooms" subtitle="Live room status across the property">
            <template #actions>
                <Link href="/front-desk/reservations" class="wh-btn-secondary">Reservations</Link>
                <button v-if="canCheckInGuest()" type="button" class="wh-btn-primary" @click="openCheckInModal()">
                    Check in guest
                </button>
            </template>
        </PageHeader>

        <PageDataSection keys="rooms">
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

        <DataTable list-title="Room list" selectable :columns="columns" :rows="rooms" empty-message="No rooms match this filter.">
            <template #cell-room_type="{ row }">
                {{ row.room_type?.name ?? '—' }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <div v-if="canChangeStatus(row)" class="flex justify-end gap-2">
                    <button
                        v-if="row.status !== 'available'"
                        type="button"
                        class="text-xs font-medium text-teal-700 hover:text-teal-900"
                        @click="setRoomStatus(row.id, 'available', 'available')"
                    >
                        Available
                    </button>
                    <button
                        v-if="row.status !== 'cleaning'"
                        type="button"
                        class="text-xs font-medium text-slate-600 hover:text-slate-900"
                        @click="setRoomStatus(row.id, 'cleaning', 'cleaning')"
                    >
                        Cleaning
                    </button>
                    <button
                        v-if="row.status !== 'maintenance'"
                        type="button"
                        class="text-xs font-medium text-amber-800 hover:text-amber-950"
                        @click="setRoomStatus(row.id, 'maintenance', 'maintenance')"
                    >
                        Maintenance
                    </button>
                </div>
                <span v-else-if="row.status === 'occupied'" class="text-xs text-slate-400">In-house</span>
            </template>
        </DataTable>
        </PageDataSection>

        <CheckInModal
            v-if="canCheckInGuest()"
            :open="showCheckInModal"
            :page-load="checkInLoad"
            :initial-guest-id="checkInGuestId"
            @close="closeCheckInModal"
        />
    </AppLayout>
</template>
