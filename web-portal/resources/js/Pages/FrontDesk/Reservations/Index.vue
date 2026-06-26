<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    reservations: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: '' }) },
    roomTypes: { type: Array, default: () => [] },
    guests: { type: Array, default: () => [] },
    defaultCheckIn: { type: String, default: '' },
    defaultCheckOut: { type: String, default: '' },
});

const showBookModal = ref(false);

const form = useForm({
    guest_id: '',
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    room_type_id: props.roomTypes[0]?.id ?? '',
    check_in_date: props.defaultCheckIn,
    check_out_date: props.defaultCheckOut,
    adults: 1,
    notes: '',
});

watch(
    () => form.guest_id,
    (guestId) => {
        const guest = props.guests.find((row) => String(row.id) === String(guestId));
        if (!guest) {
            return;
        }

        form.guest_name = guest.full_name ?? '';
        form.guest_email = guest.email ?? '';
        form.guest_phone = guest.phone ?? '';
    },
);

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

function openBookModal() {
    form.reset();
    form.room_type_id = props.roomTypes[0]?.id ?? '';
    form.check_in_date = props.defaultCheckIn;
    form.check_out_date = props.defaultCheckOut;
    form.adults = 1;
    showBookModal.value = true;
}

function closeBookModal() {
    showBookModal.value = false;
}

function submitBooking() {
    form.post('/front-desk/reservations', {
        preserveScroll: true,
        onSuccess: () => closeBookModal(),
    });
}
</script>

<template>
    <AppLayout title="Reservations">
        <PageHeader title="Reservations" subtitle="Arrivals, in-house guests, and history">
            <template #actions>
                <button type="button" class="wh-btn-secondary" @click="openBookModal">Book reservation</button>
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

        <FormModal
            :open="showBookModal"
            title="Book reservation"
            subtitle="Create a future stay — check in when the guest arrives"
            @close="closeBookModal"
        >
            <form class="space-y-4" @submit.prevent="submitBooking">
                <div>
                    <label for="guest_id" class="mb-1 block text-sm font-medium text-slate-700">Guest profile (optional)</label>
                    <select id="guest_id" v-model="form.guest_id" class="wh-input">
                        <option value="">New guest details below</option>
                        <option v-for="guest in guests" :key="guest.id" :value="guest.id">
                            {{ guest.full_name }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="guest_name" class="mb-1 block text-sm font-medium text-slate-700">Guest name</label>
                        <input id="guest_name" v-model="form.guest_name" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label for="room_type_id" class="mb-1 block text-sm font-medium text-slate-700">Room type</label>
                        <select id="room_type_id" v-model="form.room_type_id" required class="wh-input">
                            <option v-for="type in roomTypes" :key="type.id" :value="type.id">
                                {{ type.name }} (ETB {{ type.base_rate }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label for="guest_email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input id="guest_email" v-model="form.guest_email" type="email" class="wh-input" />
                    </div>
                    <div>
                        <label for="guest_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input id="guest_phone" v-model="form.guest_phone" type="text" class="wh-input" />
                    </div>
                    <div>
                        <label for="check_in_date" class="mb-1 block text-sm font-medium text-slate-700">Check-in date</label>
                        <input id="check_in_date" v-model="form.check_in_date" type="date" required class="wh-input" />
                    </div>
                    <div>
                        <label for="check_out_date" class="mb-1 block text-sm font-medium text-slate-700">Check-out date</label>
                        <input id="check_out_date" v-model="form.check_out_date" type="date" required class="wh-input" />
                    </div>
                    <div>
                        <label for="adults" class="mb-1 block text-sm font-medium text-slate-700">Adults</label>
                        <input id="adults" v-model.number="form.adults" type="number" min="1" max="10" class="wh-input" />
                    </div>
                </div>
                <div>
                    <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notes</label>
                    <textarea id="notes" v-model="form.notes" rows="2" class="wh-input" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeBookModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitBooking">
                        Create reservation
                    </button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
