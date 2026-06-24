<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    roomTypes: { type: Array, default: () => [] },
    defaultCheckIn: { type: String, required: true },
    defaultCheckOut: { type: String, required: true },
});

const form = useForm({
    group_name: '',
    contact_name: '',
    contact_email: '',
    check_in_date: props.defaultCheckIn,
    check_out_date: props.defaultCheckOut,
    rooms: [
        { guest_name: '', room_type_id: props.roomTypes[0]?.id ?? '' },
        { guest_name: '', room_type_id: props.roomTypes[0]?.id ?? '' },
    ],
});

function addRoom() {
    form.rooms.push({
        guest_name: '',
        room_type_id: props.roomTypes[0]?.id ?? '',
    });
}

function removeRoom(index) {
    if (form.rooms.length > 1) {
        form.rooms.splice(index, 1);
    }
}

function submit() {
    form.post('/group-bookings');
}
</script>

<template>
    <AppLayout title="Create group booking">
        <PageHeader title="Create group booking" subtitle="Rooming list with one reservation per room">
            <template #actions>
                <Link href="/group-bookings" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-3xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="group_name" class="mb-1 block text-sm font-medium text-slate-700">Group name</label>
                    <input id="group_name" v-model="form.group_name" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="contact_name" class="mb-1 block text-sm font-medium text-slate-700">Contact name</label>
                    <input id="contact_name" v-model="form.contact_name" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="contact_email" class="mb-1 block text-sm font-medium text-slate-700">Contact email</label>
                    <input id="contact_email" v-model="form.contact_email" type="email" class="wh-input" />
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

            <div class="mt-6">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Rooming list</h3>
                <div class="space-y-3">
                    <div
                        v-for="(room, index) in form.rooms"
                        :key="index"
                        class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-2"
                    >
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Guest name</label>
                            <input v-model="room.guest_name" type="text" required class="wh-input" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Room type</label>
                            <select v-model="room.room_type_id" required class="wh-input">
                                <option v-for="type in roomTypes" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button
                                v-if="form.rooms.length > 1"
                                type="button"
                                class="text-xs text-red-600 hover:underline"
                                @click="removeRoom(index)"
                            >
                                Remove room
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="wh-btn-secondary mt-3" @click="addRoom">Add room</button>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create group booking</button>
            </div>
        </form>
    </AppLayout>
</template>
