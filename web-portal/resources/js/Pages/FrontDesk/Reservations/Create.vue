<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    roomTypes: { type: Array, default: () => [] },
    guests: { type: Array, default: () => [] },
    defaultCheckIn: { type: String, required: true },
    defaultCheckOut: { type: String, required: true },
});

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

function submit() {
    form.post('/front-desk/reservations');
}
</script>

<template>
    <AppLayout title="New reservation">
        <PageHeader
            title="Book reservation"
            subtitle="Create a future stay — check in when the guest arrives"
        >
            <template #actions>
                <Link href="/front-desk/reservations" class="wh-btn-secondary">All reservations</Link>
                <Link href="/front-desk/rooms?open=check-in" class="wh-btn-secondary">Walk-in check-in</Link>
            </template>
        </PageHeader>

        <form class="wh-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
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
            <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create reservation</button>
        </form>
    </AppLayout>
</template>
