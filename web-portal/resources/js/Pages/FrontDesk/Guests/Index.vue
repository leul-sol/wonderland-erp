<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CheckInModal from '../../../Components/FrontDesk/CheckInModal.vue';
import GuestFormFields from '../../../Components/FrontDesk/GuestFormFields.vue';
import DataTable from '../../../Components/DataTable.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import { useCheckInModal } from '../../../composables/useCheckInModal';
import { useQueryModal } from '../../../composables/useQueryModal';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    guests: { type: Array, default: () => [] },
    checkInLoad: { type: Object, default: null },
    checkInGuestId: { type: Number, default: null },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingGuest = ref(null);
const pendingEditId = ref(null);

const { canManageGuests } = usePortalPermission();
const { showCheckInModal, checkInGuestId, openCheckInModal, closeCheckInModal, canCheckInGuest } = useCheckInModal(
    props.checkInGuestId,
);

const createForm = useForm({
    full_name: '',
    phone: '',
    email: '',
    id_document_type: '',
    id_document_number: '',
    nationality: '',
    address: '',
});

const editForm = useForm({
    full_name: '',
    phone: '',
    email: '',
    id_document_type: '',
    id_document_number: '',
    nationality: '',
    address: '',
});

const columns = [
    { key: 'full_name', label: 'Name' },
    { key: 'phone', label: 'Phone' },
    { key: 'email', label: 'Email' },
    { key: 'nationality', label: 'Nationality' },
    { key: 'actions', label: '', class: 'text-right' },
];

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    createForm.post('/front-desk/guests', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function openEditModal(guest) {
    editingGuest.value = guest;
    editForm.full_name = guest.full_name ?? '';
    editForm.phone = guest.phone ?? '';
    editForm.email = guest.email ?? '';
    editForm.id_document_type = guest.id_document_type ?? '';
    editForm.id_document_number = guest.id_document_number ?? '';
    editForm.nationality = guest.nationality ?? '';
    editForm.address = guest.address ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingGuest.value = null;
}

function submitEdit() {
    if (!editingGuest.value) {
        return;
    }

    editForm.put(`/front-desk/guests/${editingGuest.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

useQueryModal(showCreateModal, {
    when: () => canManageGuests(),
    onOpen: openCreateModal,
});

useQueryModal(showEditModal, {
    expected: 'edit',
    when: () => canManageGuests(),
    onOpen: (params) => {
        const id = Number.parseInt(params.get('id') ?? '', 10);

        if (!id) {
            return;
        }

        const guest = props.guests.find((row) => row.id === id);

        if (guest) {
            openEditModal(guest);
        } else {
            pendingEditId.value = id;
        }
    },
});

watch(
    () => props.guests,
    (rows) => {
        if (!pendingEditId.value || rows.length === 0) {
            return;
        }

        const guest = rows.find((row) => row.id === pendingEditId.value);

        if (guest) {
            openEditModal(guest);
            pendingEditId.value = null;
        }
    },
);
</script>

<template>
    <AppLayout title="Guest profiles">
        <PageHeader title="Guest profiles" subtitle="Registered guests for reservations and folios">
            <template #actions>
                <button v-if="canManageGuests()" type="button" class="wh-btn-primary" @click="openCreateModal">Add guest</button>
                <button v-if="canCheckInGuest()" type="button" class="wh-btn-secondary" @click="openCheckInModal()">
                    Check in
                </button>
            </template>
        </PageHeader>

        <PageDataSection keys="guests">
        <DataTable list-title="Guests" :columns="columns" :rows="guests" empty-message="No guest profiles yet.">
            <template #empty>
                <EmptyState
                    title="No guest profiles yet"
                    description="Register repeat guests to speed up reservations. Walk-in guests can be checked in without a profile."
                    variant="table"
                >
                    <template #action>
                        <div class="flex flex-wrap justify-center gap-2">
                            <button v-if="canManageGuests()" type="button" class="wh-btn-primary" @click="openCreateModal">
                                Add your first guest
                            </button>
                            <button v-if="canCheckInGuest()" type="button" class="wh-btn-secondary" @click="openCheckInModal()">
                                Check in walk-in
                            </button>
                        </div>
                    </template>
                </EmptyState>
            </template>
            <template #cell-full_name="{ row }">
                <button
                    v-if="canManageGuests()"
                    type="button"
                    class="wh-table-link text-left"
                    @click="openEditModal(row)"
                >
                    {{ row.full_name }}
                </button>
                <span v-else>{{ row.full_name }}</span>
            </template>
            <template #cell-actions="{ row }">
                <RowActions
                    :items="[
                        ...(canManageGuests() ? [{ label: 'Edit', onClick: () => openEditModal(row) }] : []),
                        ...(canCheckInGuest() ? [{ label: 'Check in', onClick: () => openCheckInModal(row.id) }] : []),
                    ]"
                />
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canManageGuests()"
            :open="showCreateModal"
            title="New guest profile"
            subtitle="Register a guest before check-in"
            size="lg"
            @close="closeCreateModal"
        >
            <form @submit.prevent="submitCreate">
                <GuestFormFields :form="createForm" />
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">Create guest</button>
                </div>
            </template>
        </FormModal>

        <FormModal
            v-if="canManageGuests()"
            :open="showEditModal"
            :title="editingGuest?.full_name ?? 'Edit guest'"
            subtitle="Update guest details for reservations and folios"
            size="lg"
            @close="closeEditModal"
        >
            <form @submit.prevent="submitEdit">
                <GuestFormFields :form="editForm" />
            </form>
            <template #footer>
                <div class="flex items-center justify-between gap-3">
                    <Link
                        v-if="editingGuest && canCheckInGuest()"
                        :href="`/front-desk/guests?open=check-in&guest_id=${editingGuest.id}`"
                        class="wh-btn-outline text-sm"
                        @click="closeEditModal"
                    >
                        Check in guest
                    </Link>
                    <div class="ml-auto flex gap-3">
                        <button type="button" class="wh-btn-secondary" @click="closeEditModal">Cancel</button>
                        <button type="button" class="wh-btn-primary" :disabled="editForm.processing" @click="submitEdit">Save changes</button>
                    </div>
                </div>
            </template>
        </FormModal>

        <CheckInModal
            v-if="canCheckInGuest()"
            :open="showCheckInModal"
            :page-load="checkInLoad"
            :initial-guest-id="checkInGuestId"
            @close="closeCheckInModal"
        />
    </AppLayout>
</template>
