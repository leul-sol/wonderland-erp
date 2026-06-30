<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import FormLabel from '../../../Components/FormLabel.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import { useQueryModal } from '../../../composables/useQueryModal';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
});

const roomTypes = computed(() => props.pageLoad?.roomTypes ?? []);
const rooms = computed(() => props.pageLoad?.rooms ?? []);

const { canManageHotelSettings } = usePortalPermission();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingRoom = ref(null);
const pendingEditId = ref(null);

const createForm = useForm({
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
    { key: 'room_number', label: 'Room' },
    { key: 'room_type', label: 'Type' },
    { key: 'floor', label: 'Floor' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function openCreateModal() {
    createForm.reset();
    createForm.room_type_id = roomTypes.value[0]?.id ?? '';
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
}

function submitCreate() {
    createForm.post('/front-desk/settings/rooms', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function openEditModal(room) {
    editingRoom.value = room;
    editForm.room_number = room.room_number ?? '';
    editForm.room_type_id = room.room_type?.id ?? room.room_type_id ?? '';
    editForm.floor = room.floor ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingRoom.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit() {
    if (!editingRoom.value) {
        return;
    }

    editForm.put(`/front-desk/settings/rooms/${editingRoom.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

useQueryModal(showCreateModal, {
    when: () => canManageHotelSettings(),
    onOpen: openCreateModal,
});

useQueryModal(showEditModal, {
    expected: 'edit',
    when: () => canManageHotelSettings(),
    onOpen: (params) => {
        const id = Number.parseInt(params.get('id') ?? '', 10);

        if (!id) {
            return;
        }

        const room = rooms.value.find((row) => row.id === id);

        if (room) {
            openEditModal(room);
        } else {
            pendingEditId.value = id;
        }
    },
});

watch(rooms, (rows) => {
    if (!pendingEditId.value || rows.length === 0) {
        return;
    }

    const room = rows.find((row) => row.id === pendingEditId.value);

    if (room) {
        openEditModal(room);
        pendingEditId.value = null;
    }
});
</script>

<template>
    <AppLayout title="Physical rooms">
        <PageHeader title="Physical rooms" subtitle="Room numbers linked to types for check-in assignment">
            <template #actions>
                <Link href="/front-desk/settings" class="wh-btn-outline">Room types</Link>
                <Link href="/front-desk/rooms" class="wh-btn-secondary">Room status board</Link>
                <button v-if="canManageHotelSettings()" type="button" class="wh-btn-primary" @click="openCreateModal">
                    <Plus class="h-4 w-4" />
                    Add room
                </button>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <DataTable list-title="Rooms" :columns="columns" :rows="rooms" empty-message="No rooms configured.">
            <template #empty>
                <EmptyState
                    title="No physical rooms yet"
                    description="Add room numbers and link each one to a room type so reception can assign them at check-in."
                    variant="table"
                >
                    <template #action>
                        <button v-if="canManageHotelSettings()" type="button" class="wh-btn-primary" @click="openCreateModal">
                            Add your first room
                        </button>
                    </template>
                </EmptyState>
            </template>
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
                <button
                    v-if="canManageHotelSettings()"
                    type="button"
                    class="wh-btn-secondary text-xs"
                    @click="openEditModal(row)"
                >
                    Edit
                </button>
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canManageHotelSettings()"
            :open="showCreateModal"
            title="Add room"
            subtitle="Link a room number to a type and floor"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <FormLabel for="room_number" required>Room number</FormLabel>
                        <input id="room_number" v-model="createForm.room_number" type="text" required class="wh-input" placeholder="201" />
                    </div>
                    <div>
                        <FormLabel for="room_floor">Floor</FormLabel>
                        <input id="room_floor" v-model="createForm.floor" type="text" class="wh-input" placeholder="2" />
                    </div>
                    <div class="sm:col-span-2">
                        <FormLabel for="room_type_id" required>Room type</FormLabel>
                        <select id="room_type_id" v-model="createForm.room_type_id" required class="wh-input">
                            <option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">Add room</button>
                </div>
            </template>
        </FormModal>

        <FormModal
            v-if="canManageHotelSettings()"
            :open="showEditModal"
            :title="`Edit room ${editingRoom?.room_number ?? ''}`"
            subtitle="Update room number, type, or floor"
            @close="closeEditModal"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <FormLabel for="edit_room_number" required>Room number</FormLabel>
                        <input id="edit_room_number" v-model="editForm.room_number" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="edit_room_floor">Floor</FormLabel>
                        <input id="edit_room_floor" v-model="editForm.floor" type="text" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <FormLabel for="edit_room_type_id" required>Room type</FormLabel>
                        <select id="edit_room_type_id" v-model="editForm.room_type_id" required class="wh-input">
                            <option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeEditModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="editForm.processing" @click="submitEdit">Save changes</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
