<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    roomTypes: { type: Array, default: () => [] },
    availableRooms: { type: Array, default: () => [] },
});

const today = new Date().toISOString().slice(0, 10);
const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

const form = useForm({
    guest_name: '',
    guest_email: '',
    room_type_id: props.roomTypes[0]?.id ?? '',
    room_id: '',
    check_in_date: today,
    check_out_date: tomorrow,
});

const roomsForType = computed(() =>
    props.availableRooms.filter((room) => String(room.room_type?.id) === String(form.room_type_id)),
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
            subtitle="Create reservation, assign room, and open folio in one step"
        />

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="guest_name" class="mb-1 block text-sm font-medium text-slate-700">Guest name</label>
                    <input id="guest_name" v-model="form.guest_name" type="text" required class="wh-input" />
                </div>

                <div class="sm:col-span-2">
                    <label for="guest_email" class="mb-1 block text-sm font-medium text-slate-700">Email (optional)</label>
                    <input id="guest_email" v-model="form.guest_email" type="email" class="wh-input" />
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

            <div class="mt-6 flex justify-end gap-3">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Checking in…' : 'Check in and open folio' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
