<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import FormModal from '../FormModal.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    pageLoad: { type: Object, default: null },
    initialGuestId: { type: [String, Number], default: '' },
});

const emit = defineEmits(['close']);

const today = new Date().toISOString().slice(0, 10);
const tomorrow = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

const roomTypes = computed(() => props.pageLoad?.roomTypes ?? []);
const availableRooms = computed(() => props.pageLoad?.availableRooms ?? []);
const guests = computed(() => props.pageLoad?.guests ?? []);
const isLoading = computed(() => props.open && props.pageLoad === null);

const form = useForm({
    guest_id: '',
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    room_type_id: '',
    room_id: '',
    check_in_date: today,
    check_out_date: tomorrow,
});

const roomsForType = computed(() =>
    availableRooms.value.filter((room) => String(room.room_type?.id) === String(form.room_type_id)),
);

const selectedRoomType = computed(() =>
    roomTypes.value.find((type) => String(type.id) === String(form.room_type_id)) ?? null,
);

const canSubmit = computed(() => roomsForType.value.length > 0 && Boolean(form.room_id));

const roomAssignHelp = computed(() => {
    if (!form.room_type_id || isLoading.value) {
        return '';
    }

    if (roomsForType.value.length === 0) {
        const typeName = selectedRoomType.value?.name ?? 'this room type';

        return `No available ${typeName} rooms right now. Check Front desk → Room status to free a room, or choose another room type.`;
    }

    if (!form.room_id) {
        return 'Select an available room before checking in.';
    }

    return '';
});

const selectedGuest = computed(() =>
    guests.value.find((guest) => String(guest.id) === String(form.guest_id)) ?? null,
);

function resetForm() {
    form.reset();
    form.guest_id = props.initialGuestId ?? '';
    form.check_in_date = today;
    form.check_out_date = tomorrow;
    form.room_type_id = roomTypes.value[0]?.id ?? '';
    form.room_id = roomsForType.value[0]?.id ?? '';

    const guest = guests.value.find((row) => String(row.id) === String(form.guest_id));
    if (guest) {
        form.guest_name = guest.full_name ?? '';
        form.guest_email = guest.email ?? '';
        form.guest_phone = guest.phone ?? '';
    }
}

watch(
    () => props.pageLoad,
    (load) => {
        if (load && props.open) {
            resetForm();
        }
    },
);

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            resetForm();
        }
    },
);

watch(
    () => props.initialGuestId,
    (guestId) => {
        if (props.open) {
            form.guest_id = guestId ?? '';
        }
    },
);

watch(
    () => form.guest_id,
    (guestId) => {
        const guest = guests.value.find((row) => String(row.id) === String(guestId));
        if (guest) {
            form.guest_name = guest.full_name ?? '';
            form.guest_email = guest.email ?? '';
            form.guest_phone = guest.phone ?? '';
        }
    },
);

watch(
    roomTypes,
    (types) => {
        if (!form.room_type_id && types[0]) {
            form.room_type_id = types[0].id;
        }
    },
    { immediate: true },
);

watch(
    () => form.room_type_id,
    () => {
        form.room_id = roomsForType.value[0]?.id ?? '';
    },
);

function submit() {
    if (!form.room_id) {
        form.setError(
            'room_id',
            roomAssignHelp.value || 'Select an available room before checking in.',
        );

        return;
    }

    form.post('/front-desk/check-in', {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}

function formatRate(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <FormModal
        :open="open"
        title="Check in guest"
        subtitle="Select a guest or enter walk-in details, assign a room, and open the folio"
        size="lg"
        @close="emit('close')"
    >
        <div v-if="isLoading" class="py-8 text-center text-sm text-slate-500">Loading rooms and guests…</div>

        <form v-else class="space-y-4" @submit.prevent="submit">
            <div
                v-if="form.errors.room_id || form.errors.guest_name || form.errors.room_type_id"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                role="alert"
            >
                <p v-if="form.errors.room_id">{{ form.errors.room_id }}</p>
                <p v-if="form.errors.room_type_id">{{ form.errors.room_type_id }}</p>
                <p v-if="form.errors.guest_name">{{ form.errors.guest_name }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="checkin_guest_id" class="mb-1 block text-sm font-medium text-slate-700">Existing guest (optional)</label>
                    <select id="checkin_guest_id" v-model="form.guest_id" class="wh-input">
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
                    <label for="checkin_guest_name" class="mb-1 block text-sm font-medium text-slate-700">Guest name</label>
                    <input id="checkin_guest_name" v-model="form.guest_name" type="text" required class="wh-input" />
                </div>

                <div>
                    <label for="checkin_guest_email" class="mb-1 block text-sm font-medium text-slate-700">Email (optional)</label>
                    <input id="checkin_guest_email" v-model="form.guest_email" type="email" class="wh-input" />
                </div>

                <div>
                    <label for="checkin_guest_phone" class="mb-1 block text-sm font-medium text-slate-700">Phone (optional)</label>
                    <input id="checkin_guest_phone" v-model="form.guest_phone" type="text" class="wh-input" />
                </div>

                <div>
                    <label for="checkin_room_type_id" class="mb-1 block text-sm font-medium text-slate-700">Room type</label>
                    <select id="checkin_room_type_id" v-model="form.room_type_id" required class="wh-input">
                        <option v-for="type in roomTypes" :key="type.id" :value="type.id">
                            {{ type.name }} — ETB {{ formatRate(type.base_rate) }}/night
                        </option>
                    </select>
                </div>

                <div>
                    <label for="checkin_room_id" class="mb-1 block text-sm font-medium text-slate-700">Assign room</label>
                    <select
                        id="checkin_room_id"
                        v-model="form.room_id"
                        required
                        class="wh-input"
                        :class="form.errors.room_id ? 'border-red-300 ring-red-100' : ''"
                        :disabled="roomsForType.length === 0"
                    >
                        <option value="" disabled>Select available room</option>
                        <option v-for="room in roomsForType" :key="room.id" :value="room.id">
                            {{ room.room_number }} — floor {{ room.floor }}
                        </option>
                    </select>
                    <p v-if="form.errors.room_id" class="mt-1 text-sm text-red-600">{{ form.errors.room_id }}</p>
                    <p v-else-if="roomAssignHelp" class="mt-1 text-sm text-amber-800">{{ roomAssignHelp }}</p>
                </div>

                <div>
                    <label for="checkin_check_in_date" class="mb-1 block text-sm font-medium text-slate-700">Check-in</label>
                    <input id="checkin_check_in_date" v-model="form.check_in_date" type="date" required class="wh-input" />
                </div>

                <div>
                    <label for="checkin_check_out_date" class="mb-1 block text-sm font-medium text-slate-700">Check-out</label>
                    <input id="checkin_check_out_date" v-model="form.check_out_date" type="date" required class="wh-input" />
                </div>
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-3">
                <button type="button" class="wh-btn-secondary" @click="emit('close')">Cancel</button>
                <button
                    type="button"
                    class="wh-btn-primary"
                    :disabled="form.processing || isLoading || !canSubmit"
                    @click="submit"
                >
                    Check in and open folio
                </button>
            </div>
        </template>
    </FormModal>
</template>
