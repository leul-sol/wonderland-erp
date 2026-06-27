<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

defineProps({
    guests: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);

const form = useForm({
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
    form.reset();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/front-desk/guests', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, { onOpen: openCreateModal });
</script>

<template>
    <AppLayout title="Guest profiles">
        <PageHeader title="Guest profiles" subtitle="Registered guests for reservations and folios">
            <template #actions>
                <button type="button" class="wh-btn-primary" @click="openCreateModal">Add guest</button>
                <Link href="/front-desk/check-in?from=guests" class="wh-btn-secondary">Check in</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="guests">
        <DataTable list-title="Guests" :columns="columns" :rows="guests" empty-message="No guest profiles yet.">
            <template #empty>
                <p>No guest profiles yet.</p>
                <button type="button" class="wh-btn-primary mt-3" @click="openCreateModal">Add your first guest</button>
            </template>
            <template #cell-full_name="{ row }">
                <Link :href="`/front-desk/guests/${row.id}/edit`" class="wh-table-link">{{ row.full_name }}</Link>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/front-desk/check-in?guest_id=${row.id}`" class="text-xs font-medium text-teal-700 hover:text-teal-900">
                    Check in
                </Link>
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal :open="showCreateModal" title="New guest profile" subtitle="Register a guest before check-in" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label for="full_name" class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                    <input id="full_name" v-model="form.full_name" type="text" required class="wh-input" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input id="phone" v-model="form.phone" type="text" class="wh-input" />
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" v-model="form.email" type="email" class="wh-input" />
                    </div>
                    <div>
                        <label for="id_document_type" class="mb-1 block text-sm font-medium text-slate-700">ID type</label>
                        <input id="id_document_type" v-model="form.id_document_type" type="text" class="wh-input" placeholder="Passport, National ID" />
                    </div>
                    <div>
                        <label for="id_document_number" class="mb-1 block text-sm font-medium text-slate-700">ID number</label>
                        <input id="id_document_number" v-model="form.id_document_number" type="text" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="nationality" class="mb-1 block text-sm font-medium text-slate-700">Nationality</label>
                        <input id="nationality" v-model="form.nationality" type="text" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                        <textarea id="address" v-model="form.address" rows="2" class="wh-input" />
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Create guest</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
