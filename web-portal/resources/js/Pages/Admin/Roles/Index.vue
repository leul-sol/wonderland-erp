<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

defineProps({
    roles: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canSyncPermissions: { type: Boolean, default: false },
    canBrowsePermissions: { type: Boolean, default: false },
});

const showCreateModal = ref(false);

const createForm = useForm({
    name: '',
    display_name: '',
    description: '',
});

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Administration', href: '/admin/users' },
    { label: 'Roles' },
];

const columns = [
    { key: 'name', label: 'Role' },
    { key: 'display_name', label: 'Label' },
    { key: 'type', label: 'Type' },
    { key: 'permissions_count', label: 'Permissions', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

async function deleteRole(role) {
    if (role.is_system) {
        return;
    }

    const confirmed = await confirmAction({
        title: 'Delete role',
        message: `Delete role "${role.display_name}"? Remove users from this role first.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/admin/roles/${role.id}`);
}

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    createForm.post('/admin/roles', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, { onOpen: openCreateModal });
</script>

<template>
    <AppLayout title="Roles">
        <PageHeader
            title="Roles"
            subtitle="Assign permission bundles to staff roles"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link v-if="canBrowsePermissions" href="/admin/permissions" class="wh-btn-secondary">
                    Permission catalog
                </Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">New role</button>
                <Link href="/admin/users" class="wh-btn-secondary">Users</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="roles">
        <DataTable list-title="Role list" selectable :columns="columns" :rows="roles" empty-message="No roles configured.">
            <template #cell-type="{ row }">
                <StatusBadge :status="row.is_system ? 'locked' : 'draft'" />
                <span class="sr-only">{{ row.is_system ? 'System' : 'Custom' }}</span>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Link
                        v-if="canSyncPermissions || !row.is_system"
                        :href="`/admin/roles/${row.id}`"
                        class="wh-btn-secondary text-xs"
                    >
                        {{ canSyncPermissions ? 'Permissions' : 'View' }}
                    </Link>
                    <Link
                        v-if="canUpdate"
                        :href="`/admin/roles/${row.id}/edit`"
                        class="wh-btn-outline text-xs"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="canDelete && !row.is_system"
                        type="button"
                        class="wh-btn-outline text-xs text-red-700"
                        @click="deleteRole(row)"
                    >
                        Delete
                    </button>
                </div>
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal :open="showCreateModal" title="Create custom role" subtitle="System roles are seeded; add roles for special access bundles" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Role slug</label>
                    <input id="name" v-model="createForm.name" type="text" required pattern="[A-Za-z0-9_-]+" class="wh-input" placeholder="e.g. night_auditor" />
                    <p v-if="createForm.errors.name" class="mt-1 text-sm text-red-600">{{ createForm.errors.name }}</p>
                </div>
                <div>
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input id="display_name" v-model="createForm.display_name" type="text" required class="wh-input" />
                    <p v-if="createForm.errors.display_name" class="mt-1 text-sm text-red-600">{{ createForm.errors.display_name }}</p>
                </div>
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" v-model="createForm.description" rows="3" class="wh-input" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">Create role</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
