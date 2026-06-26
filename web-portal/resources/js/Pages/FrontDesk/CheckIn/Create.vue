<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    roomTypes: { type: Array, default: () => [] },
    availableRooms: { type: Array, default: () => [] },
    guests: { type: Array, default: () => [] },
    selectedGuestId: { type: Number, default: null },
});

const today = new Date().toISOString().slice(0, 10);
const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

const form = useForm({
    guest_id: props.selectedGuestId ?? '',
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    room_type_id: props.roomTypes[0]?.id ?? '',
    room_id: '',
    check_in_date: today,
    check_out_date: tomorrow,
});

const roomsForType = computed(() =>
    props.availableRooms.filter((room) => String(room.room_type?.id) === String(form.room_type_id)),
);

const selectedGuest = computed(() =>
    props.guests.find((guest) => String(guest.id) === String(form.guest_id)) ?? null,
);

function applyGuest(guest) {
    if (!guest) {
        return;
    }

    form.guest_name = guest.full_name ?? '';
    form.guest_email = guest.email ?? '';
    form.guest_phone = guest.phone ?? '';
}

watch(
    () => form.guest_id,
    (guestId) => {
        const guest = props.guests.find((row) => String(row.id) === String(guestId));
        applyGuest(guest);
    },
    { immediate: true },
);

watch(
    () => form.room_type_id,
    () => {
        form.room_id = roomsForType.value[0]?.id ?? '';
    },
    { immediate: true },
);

function submit() {
    form.post('/front-desk/check-in');
}
</script>

<template>
    <AppLayout title="Check in guest">
        <PageHeader
            title="Check in guest"
            subtitle="Select an existing guest or enter details, then assign a room and open the folio"
        >
            <template #actions>
                <Link href="/front-desk/guests" class="wh-btn-secondary">Guest profiles</Link>
                <Link href="/front-desk/reservations" class="wh-btn-secondary">Reservations</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="guest_id" class="mb-1 block text-sm font-medium text-slate-700">Existing guest (optional)</label>
                    <select id="guest_id" v-model="form.guest_id" class="wh-input">
                        <option value="">Walk-in / new name</option>
                        <option v-for="guest in guests" :key="guest.id" :value="guest.id">
                            {{ guest.full_name }}{{ guest.phone ? ` · ${guest.phone}` : '' }}
                        </option>
                    </select>
                    <p v-if="selectedGuest" class="mt-1 text-xs text-slate-500">
                        Profile linked —
                        <Link :href="`/front-desk/guests/${selectedGuest.id}/edit`" class="wh-table-link">edit guest</Link>
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label for="guest_name" class="mb-1 block text-sm font-medium text-slate-700">Guest name</label>
                    <input id="guest_name" v-model="form.guest_name" type="text" required class="wh-input" />
                </div>

                <div>
                    <label for="guest_email" class="mb-1 block text-sm font-medium text-slate-700">Email (optional)</label>
                    <input id="guest_email" v-model="form.guest_email" type="email" class="wh-input" />
                </div>

                <div>
                    <label for="guest_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input id="guest_phone" v-model="form.guest_phone" type="text" class="wh-input" />
                </div>

                <div>
                    <label for="room_type_id" class="mb-1 block text-sm font-medium text-slate-700">Room type</label>
                    <select id="room_type_id" v-model="form.room_type_id" required class="wh-input">
                        <option v-for="type in roomTypes" :key="type.id" :value="type.id">
                            {{ type.name }} (ETB {{ type.base_rate }}/night)
                        </option>
                    </select>
                </div>

                <div>
                    <label for="room_id" class="mb-1 block text-sm font-medium text-slate-700">Assign room</label>
                    <select id="room_id" v-model="form.room_id" required class="wh-input">
                        <option value="" disabled>Select available room</option>
                        <option v-for="room in roomsForType" :key="room.id" :value="room.id">
                            {{ room.room_number }} — floor {{ room.floor }}
                        </option>
                    </select>
                </div>

                <div>
                    <label for="check_in_date" class="mb-1 block text-sm font-medium text-slate-700">Check-in</label>
                    <input id="check_in_date" v-model="form.check_in_date" type="date" required class="wh-input" />
                </div>

                <div>
                    <label for="check_out_date" class="mb-1 block text-sm font-medium text-slate-700">Check-out</label>
                    <input id="check_out_date" v-model="form.check_out_date" type="date" required class="wh-input" />
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Check in and open folio</button>
            </div>
        </form>
    </AppLayout>
</template>
