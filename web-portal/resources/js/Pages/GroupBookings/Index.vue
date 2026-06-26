<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../Components/DataTable.vue';
import FormModal from '../../Components/FormModal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    groupBookings: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ tab: 'all' }) },
    roomTypes: { type: Array, default: () => [] },
    defaultCheckIn: { type: String, default: '' },
    defaultCheckOut: { type: String, default: '' },
});

const showCreateModal = ref(false);

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

const columns = [
    { key: 'group_code', label: 'Code' },
    { key: 'group_name', label: 'Group' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'room_count', label: 'Rooms' },
    { key: 'status', label: 'Status' },
];

const tabs = [
    { value: 'all', label: 'All' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'checked_in', label: 'In-house' },
    { value: 'checked_out', label: 'Departed' },
];

function applyTab(tab) {
    router.get('/group-bookings', tab === 'all' ? {} : { tab }, { preserveState: true, replace: true });
}

function openCreateModal() {
    form.reset();
    form.check_in_date = props.defaultCheckIn;
    form.check_out_date = props.defaultCheckOut;
    form.rooms = [
        { guest_name: '', room_type_id: props.roomTypes[0]?.id ?? '' },
        { guest_name: '', room_type_id: props.roomTypes[0]?.id ?? '' },
    ];
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

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

function submitCreate() {
    form.post('/group-bookings', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}
</script>

<template>
    <AppLayout title="Group bookings">
        <PageHeader title="Group bookings" subtitle="Rooming lists, bulk check-in, and group check-out">
            <template #actions>
                <button type="button" class="wh-btn-primary" @click="openCreateModal">Create group</button>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition"
                :class="
                    filters.tab === tab.value
                        ? 'bg-teal-700 text-white ring-teal-700'
                        : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'
                "
                @click="applyTab(tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>

        <DataTable list-title="Group booking list" selectable :columns="columns" :rows="groupBookings" empty-message="No group bookings yet.">
            <template #cell-group_code="{ row }">
                <Link :href="`/group-bookings/${row.id}`" class="wh-table-link">{{ row.group_code }}</Link>
            </template>
            <template #cell-group_name="{ row }">
                <Link :href="`/group-bookings/${row.id}`" class="wh-table-link">{{ row.group_name }}</Link>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </DataTable>

        <FormModal
            :open="showCreateModal"
            title="Create group booking"
            subtitle="Rooming list with one reservation per room"
            size="xl"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
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

                <div>
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
                            <div class="flex justify-end sm:col-span-2">
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
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">
                        Create group booking
                    </button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
