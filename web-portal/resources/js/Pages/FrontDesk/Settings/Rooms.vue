<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    rooms: { type: Array, default: () => [] },
    roomTypes: { type: Array, default: () => [] },
});

const editingRoomId = ref(null);

const createForm = useForm({
    room_number: '',
    room_type_id: props.roomTypes[0]?.id ?? '',
    floor: '',
});

const editForm = useForm({
    room_number: '',
    room_type_id: '',
    floor: '',
});

const columns = [
    { key: 'room_number', label: 'Room' },
    { key: 'room_type', label: 'Type' },
    { key: 'floor', label: 'Floor' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function submitCreate() {
    createForm.post('/front-desk/settings/rooms', {
        preserveScroll: true,
        onSuccess: () => createForm.reset('room_number', 'floor'),
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
</script>

<template>
    <AppLayout title="Rooms">
        <PageHeader title="Physical rooms" subtitle="Room numbers linked to types for check-in assignment">
            <template #actions>
                <Link href="/front-desk/settings" class="wh-btn-secondary">Room types</Link>
                <Link href="/front-desk/rooms" class="wh-btn-secondary">Room status board</Link>
            </template>
        </PageHeader>

        <form class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add room</h3>
            <div class="grid gap-3 sm:grid-cols-4">
                <input v-model="createForm.room_number" type="text" required class="wh-input" placeholder="Room number" />
                <select v-model="createForm.room_type_id" required class="wh-input">
                    <option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                </select>
                <input v-model="createForm.floor" type="text" class="wh-input" placeholder="Floor" />
                <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Add room</button>
            </div>
        </form>

        <form v-if="editingRoomId" class="wh-card mb-6 border-teal-200 p-4" @submit.prevent="submitEdit">
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

        <DataTable list-title="Rooms" :columns="columns" :rows="rooms" empty-message="No rooms configured.">
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
    </AppLayout>
</template>
