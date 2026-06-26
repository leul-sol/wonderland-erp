<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    reservation: { type: Object, required: true },
    availableRooms: { type: Array, default: () => [] },
});

const checkInForm = useForm({ room_id: props.availableRooms[0]?.id ?? '' });
const cancelForm = useForm({});
const noShowForm = useForm({});

const isConfirmed = computed(() => props.reservation.status === 'confirmed');
const isCheckedIn = computed(() => props.reservation.status === 'checked_in');
const canManage = computed(() => isConfirmed.value);

async function submitCheckIn() {
    checkInForm.post(`/front-desk/reservations/${props.reservation.id}/check-in`);
}

async function cancelReservation() {
    const ok = await confirmAction({
        title: 'Cancel reservation',
        message: `Cancel reservation ${props.reservation.confirmation_code} for ${props.reservation.guest_name}?`,
        confirmLabel: 'Cancel reservation',
        variant: 'danger',
    });

    if (!ok) {
        return;
    }

    cancelForm.put(`/front-desk/reservations/${props.reservation.id}/cancel`);
}

async function markNoShow() {
    const ok = await confirmAction({
        title: 'Mark no-show',
        message: `Mark ${props.reservation.guest_name} as no-show?`,
        confirmLabel: 'Mark no-show',
        variant: 'danger',
    });

    if (!ok) {
        return;
    }

    noShowForm.put(`/front-desk/reservations/${props.reservation.id}/no-show`);
}
</script>

<template>
    <AppLayout :title="`Reservation ${reservation.confirmation_code}`">
        <PageHeader
            :title="reservation.confirmation_code"
            :subtitle="`${reservation.guest_name} · ${reservation.check_in_date} → ${reservation.check_out_date}`"
        >
            <template #actions>
                <StatusBadge :status="reservation.status" />
                <Link href="/front-desk/reservations" class="wh-btn-secondary text-xs">All reservations</Link>
                <Link
                    v-if="reservation.guest_id"
                    :href="`/front-desk/guests/${reservation.guest_id}/edit`"
                    class="wh-btn-secondary text-xs"
                >
                    Guest profile
                </Link>
                <Link
                    v-if="reservation.folio_id"
                    :href="`/front-desk/folios/${reservation.folio_id}`"
                    class="wh-btn-primary text-xs"
                >
                    Open folio
                </Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="wh-card p-4 text-sm">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Guest</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Name</dt>
                        <dd class="font-medium text-slate-900">{{ reservation.guest_name }}</dd>
                    </div>
                    <div v-if="reservation.guest_email" class="flex justify-between gap-4">
                        <dt class="text-slate-500">Email</dt>
                        <dd>{{ reservation.guest_email }}</dd>
                    </div>
                    <div v-if="reservation.guest_phone" class="flex justify-between gap-4">
                        <dt class="text-slate-500">Phone</dt>
                        <dd>{{ reservation.guest_phone }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Adults</dt>
                        <dd>{{ reservation.adults ?? 1 }}</dd>
                    </div>
                </dl>
            </section>

            <section class="wh-card p-4 text-sm">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Stay</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Room type</dt>
                        <dd>{{ reservation.room?.room_type?.name ?? reservation.room_type_id }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Assigned room</dt>
                        <dd>{{ reservation.room?.room_number ?? 'Not assigned' }}</dd>
                    </div>
                    <div v-if="reservation.checked_in_at" class="flex justify-between gap-4">
                        <dt class="text-slate-500">Checked in</dt>
                        <dd>{{ reservation.checked_in_at }}</dd>
                    </div>
                    <div v-if="reservation.checked_out_at" class="flex justify-between gap-4">
                        <dt class="text-slate-500">Checked out</dt>
                        <dd>{{ reservation.checked_out_at }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section v-if="isConfirmed" class="wh-card mt-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Check in</h3>
            <form class="flex flex-wrap items-end gap-4" @submit.prevent="submitCheckIn">
                <div class="min-w-[12rem] flex-1">
                    <label for="room_id" class="mb-1 block text-sm font-medium text-slate-700">Assign room</label>
                    <select id="room_id" v-model="checkInForm.room_id" required class="wh-input">
                        <option value="" disabled>Select available room</option>
                        <option v-for="room in availableRooms" :key="room.id" :value="room.id">
                            {{ room.room_number }} — floor {{ room.floor }}
                        </option>
                    </select>
                    <p v-if="availableRooms.length === 0" class="mt-1 text-xs text-amber-800">No available rooms for this room type.</p>
                </div>
                <button type="submit" class="wh-btn-primary" :disabled="checkInForm.processing || availableRooms.length === 0">
                    Check in guest
                </button>
            </form>
        </section>

        <section v-if="canManage" class="mt-6 flex flex-wrap gap-3">
            <button type="button" class="wh-btn-secondary" :disabled="cancelForm.processing" @click="cancelReservation">
                Cancel reservation
            </button>
            <button type="button" class="wh-btn-secondary" :disabled="noShowForm.processing" @click="markNoShow">
                Mark no-show
            </button>
        </section>

        <p v-if="isCheckedIn && reservation.folio_id" class="mt-6 text-sm text-slate-600">
            Guest is in-house.
            <Link :href="`/front-desk/folios/${reservation.folio_id}`" class="wh-table-link">Manage folio</Link>
            to post charges, record payments, and check out.
        </p>
    </AppLayout>
</template>
