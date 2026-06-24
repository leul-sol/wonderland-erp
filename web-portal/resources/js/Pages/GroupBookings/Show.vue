<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    groupBooking: { type: Object, required: true },
    availableRooms: { type: Array, default: () => [] },
    folios: { type: Object, default: () => ({}) },
});

const checkInForm = useForm({ assignments: [] });
const settleForm = useForm({ amount: '', payment_method: 'cash' });
const checkoutForm = useForm({});

const isConfirmed = computed(() => props.groupBooking.status === 'confirmed');
const isCheckedIn = computed(() => props.groupBooking.status === 'checked_in');

const assignments = reactive(
    Object.fromEntries(
        (props.groupBooking.reservations ?? []).map((reservation) => [
            reservation.id,
            { reservation_id: reservation.id, room_id: '' },
        ]),
    ),
);

const reservationColumns = [
    { key: 'guest_name', label: 'Guest' },
    { key: 'room_type', label: 'Type' },
    { key: 'status', label: 'Status' },
    { key: 'room', label: 'Room' },
    { key: 'folio', label: 'Folio' },
];

const allFoliosSettled = computed(() => {
    const reservations = props.groupBooking.reservations ?? [];

    if (reservations.length === 0) {
        return false;
    }

    return reservations.every((reservation) => {
        const folioId = reservation.folio_id;

        if (!folioId) {
            return false;
        }

        return props.folios[folioId]?.status === 'settled';
    });
});

function roomsForType(roomTypeId) {
    return props.availableRooms.filter((room) => String(room.room_type?.id) === String(roomTypeId));
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function submitCheckIn() {
    checkInForm.assignments = Object.values(assignments).filter((row) => row.room_id);
    checkInForm.post(`/group-bookings/${props.groupBooking.id}/check-in`, { preserveScroll: true });
}

function settleFolio(folioId) {
    const folio = props.folios[folioId];
    settleForm.amount = folio?.balance ?? settleForm.amount;
    settleForm.post(`/group-bookings/${props.groupBooking.id}/folios/${folioId}/settle`, { preserveScroll: true });
}

function checkOutGroup() {
    checkoutForm.post(`/group-bookings/${props.groupBooking.id}/check-out`);
}
</script>

<template>
    <AppLayout :title="groupBooking.group_code">
        <PageHeader
            :title="groupBooking.group_name"
            :subtitle="`${groupBooking.group_code} · ${groupBooking.room_count} rooms · ${groupBooking.contact_name}`"
        >
            <template #actions>
                <StatusBadge :status="groupBooking.status" />
            </template>
        </PageHeader>

        <section v-if="isConfirmed" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Bulk check-in</h3>
            <p class="mb-4 text-sm text-slate-600">Assign an available room to each guest, then check in the group.</p>
            <div class="space-y-3">
                <div
                    v-for="reservation in groupBooking.reservations"
                    :key="reservation.id"
                    class="grid gap-3 rounded-lg border border-slate-100 p-3 sm:grid-cols-3"
                >
                    <div>
                        <p class="font-medium text-slate-900">{{ reservation.guest_name }}</p>
                        <p class="text-xs text-slate-500">{{ reservation.room_type?.name ?? 'Room type' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <select v-model="assignments[reservation.id].room_id" class="wh-input" required>
                            <option value="" disabled>Select room</option>
                            <option
                                v-for="room in roomsForType(reservation.room_type_id)"
                                :key="room.id"
                                :value="room.id"
                            >
                                {{ room.room_number }} — floor {{ room.floor }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="button" class="wh-btn-primary" :disabled="checkInForm.processing" @click="submitCheckIn">
                    Check in group
                </button>
            </div>
        </section>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Rooming list</h3>
            <DataTable :columns="reservationColumns" :rows="groupBooking.reservations ?? []">
                <template #cell-room_type="{ row }">
                    {{ row.room_type?.name ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>
                <template #cell-room="{ row }">
                    {{ row.room?.room_number ?? '—' }}
                </template>
                <template #cell-folio="{ row }">
                    <Link
                        v-if="row.folio_id"
                        :href="`/front-desk/folios/${row.folio_id}`"
                        class="text-teal-700 hover:underline"
                    >
                        #{{ row.folio_id }}
                    </Link>
                    <span v-else>—</span>
                </template>
            </DataTable>
        </section>

        <section v-if="isCheckedIn" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Settle folios</h3>
            <div class="space-y-4">
                <div
                    v-for="reservation in groupBooking.reservations"
                    :key="`folio-${reservation.id}`"
                    class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-100 pb-4 last:border-0"
                >
                    <div v-if="reservation.folio_id && folios[reservation.folio_id]">
                        <p class="font-medium text-slate-900">{{ reservation.guest_name }}</p>
                        <p class="wh-money text-sm">
                            Folio #{{ reservation.folio_id }} · ETB {{ formatMoney(folios[reservation.folio_id].balance) }}
                        </p>
                        <StatusBadge :status="folios[reservation.folio_id].status" />
                    </div>
                    <div
                        v-if="reservation.folio_id && folios[reservation.folio_id]?.status === 'open'"
                        class="flex flex-wrap items-end gap-2"
                    >
                        <input v-model="settleForm.amount" type="number" step="0.01" class="wh-input w-32" />
                        <select v-model="settleForm.payment_method" class="wh-input w-28">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank</option>
                        </select>
                        <button
                            type="button"
                            class="wh-btn-primary text-xs"
                            :disabled="settleForm.processing"
                            @click="settleFolio(reservation.folio_id)"
                        >
                            Settle
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="isCheckedIn" class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Group check-out</h3>
            <p v-if="!allFoliosSettled" class="mb-3 text-sm text-amber-800">
                Settle all guest folios before releasing the group.
            </p>
            <button
                type="button"
                class="wh-btn-primary"
                :disabled="!allFoliosSettled || checkoutForm.processing"
                @click="checkOutGroup"
            >
                Check out group
            </button>
        </section>
    </AppLayout>
</template>
