<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
});

const roomTypes = computed(() => props.pageLoad?.roomTypes ?? []);
const rooms = computed(() => props.pageLoad?.rooms ?? []);

const showRoomsModal = ref(false);
const editingRoomId = ref(null);

const createForm = useForm({
    name: '',
    code: '',
    base_rate: '',
    max_occupancy: 2,
});

const roomForm = useForm({
    room_number: '',
    room_type_id: '',
    floor: '',
});

const editForm = useForm({
    room_number: '',
    room_type_id: '',
    floor: '',
});

const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Room type' },
    { key: 'base_rate', label: 'Base rate', class: 'text-right' },
    { key: 'max_occupancy', label: 'Max guests', class: 'text-right' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const roomColumns = [
    { key: 'room_number', label: 'Room' },
    { key: 'room_type', label: 'Type' },
    { key: 'floor', label: 'Floor' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function submitCreate() {
    createForm.post('/front-desk/settings/room-types', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function openRoomsModal() {
    roomForm.reset();
    roomForm.room_type_id = roomTypes.value[0]?.id ?? '';
    editingRoomId.value = null;
    showRoomsModal.value = true;
}

function closeRoomsModal() {
    showRoomsModal.value = false;
    editingRoomId.value = null;
}

function submitRoomCreate() {
    roomForm.post('/front-desk/settings/rooms', {
        preserveScroll: true,
        onSuccess: () => roomForm.reset('room_number', 'floor'),
    });
}

function startEdit(room) {
    editingRoomId.value = room.id;
    editForm.room_number = room.room_number ?? '';
    editForm.room_type_id = room.room_type?.id ?? room.room_type_id ?? '';
    editForm.floor = room.floor ?? '';
}

function cancelEdit() {
    editingRoomId.value = null;
    editForm.reset();
}

function submitEdit() {
    if (!editingRoomId.value) {
        return;
    }

    editForm.put(`/front-desk/settings/rooms/${editingRoomId.value}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
}

async function toggleActive(roomType) {
    const deactivating = roomType.is_active !== false;
    const ok = await confirmAction({
        title: deactivating ? 'Deactivate room type' : 'Activate room type',
        message: deactivating
            ? `Hide "${roomType.name}" from new reservations?`
            : `Restore "${roomType.name}" for bookings?`,
        confirmLabel: deactivating ? 'Deactivate' : 'Activate',
    });

    if (!ok) {
        return;
    }

    useForm({ is_active: !deactivating }).put(`/front-desk/settings/room-types/${roomType.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Hotel settings">
        <PageHeader title="Hotel settings" subtitle="Room types, physical rooms, and rack rates">
            <template #actions>
                <button type="button" class="wh-btn-secondary" @click="openRoomsModal">Physical rooms</button>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <form class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add room type</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input v-model="createForm.code" type="text" required class="wh-input" placeholder="Code (STD)" />
                <input v-model="createForm.name" type="text" required class="wh-input" placeholder="Name" />
                <MoneyField v-model="createForm.base_rate" placeholder="Base rate" required />
                <input v-model.number="createForm.max_occupancy" type="number" min="1" required class="wh-input" placeholder="Max guests" />
                <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Add</button>
            </div>
        </form>

        <DataTable list-title="Room types" :columns="columns" :rows="roomTypes" empty-message="No room types configured.">
            <template #cell-base_rate="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.base_rate) }}</span>
            </template>
            <template #cell-is_active="{ row }">
                <StatusBadge :status="row.is_active === false ? 'inactive' : 'active'" />
            </template>
            <template #cell-actions="{ row }">
                <button type="button" class="wh-btn-secondary text-xs" @click="toggleActive(row)">
                    {{ row.is_active === false ? 'Activate' : 'Deactivate' }}
                </button>
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            :open="showRoomsModal"
            title="Physical rooms"
            subtitle="Room numbers linked to types for check-in assignment"
            size="xl"
            @close="closeRoomsModal"
        >
            <form class="mb-6 rounded-lg border border-slate-200 p-4" @submit.prevent="submitRoomCreate">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add room</h3>
                <div class="grid gap-3 sm:grid-cols-4">
                    <input v-model="roomForm.room_number" type="text" required class="wh-input" placeholder="Room number" />
                    <select v-model="roomForm.room_type_id" required class="wh-input">
                        <option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                    <input v-model="roomForm.floor" type="text" class="wh-input" placeholder="Floor" />
                    <button type="submit" class="wh-btn-primary" :disabled="roomForm.processing">Add room</button>
                </div>
            </form>

            <form v-if="editingRoomId" class="mb-6 rounded-lg border border-teal-200 p-4" @submit.prevent="submitEdit">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Edit room #{{ editingRoomId }}</h3>
                <div class="grid gap-3 sm:grid-cols-4">
                    <input v-model="editForm.room_number" type="text" required class="wh-input" />
                    <select v-model="editForm.room_type_id" required class="wh-input">
                        <option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                    <input v-model="editForm.floor" type="text" class="wh-input" placeholder="Floor" />
                    <div class="flex gap-2">
                        <button type="submit" class="wh-btn-primary" :disabled="editForm.processing">Save</button>
                        <button type="button" class="wh-btn-secondary" @click="cancelEdit">Cancel</button>
                    </div>
                </div>
            </form>

            <DataTable list-title="Rooms" :columns="roomColumns" :rows="rooms" empty-message="No rooms configured.">
                <template #cell-room_type="{ row }">
                    {{ row.room_type?.name ?? row.room_type?.code ?? '—' }}
                </template>
                <template #cell-floor="{ row }">
                    {{ row.floor ?? '—' }}
                </template>
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <button type="button" class="wh-btn-secondary text-xs" @click="startEdit(row)">Edit</button>
                </template>
            </DataTable>

            <template #footer>
                <div class="flex justify-end">
                    <Link href="/front-desk/rooms" class="wh-btn-secondary mr-3">Room status board</Link>
                    <button type="button" class="wh-btn-primary" @click="closeRoomsModal">Done</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
