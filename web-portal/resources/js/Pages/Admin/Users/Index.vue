<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    search: { type: String, default: '' },
    canCreate: { type: Boolean, default: false },
    canDeactivate: { type: Boolean, default: false },
    canAssignRoles: { type: Boolean, default: false },
});

const users = computed(() => props.pageLoad?.users ?? []);
const meta = computed(() => props.pageLoad?.meta ?? null);

const showCreateModal = ref(false);

const createForm = useForm({
    username: '',
    email: '',
    password: '',
    display_name: '',
    employee_id: '',
});

const columns = [
    { key: 'username', label: 'ID', sortable: true },
    { key: 'display_name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Roles' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Action', class: 'text-right w-16' },
];

const sortOptions = [
    { label: 'Sort By A-Z', value: 'username_asc' },
    { label: 'Sort By Z-A', value: 'username_desc' },
    { label: 'Newest first', value: 'newest' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Administration', href: '/admin/users' },
    { label: 'Users' },
];

function userStatus(user) {
    return user.is_active ? 'active' : 'inactive';
}

function roleNames(user) {
    return (user.roles ?? []).map((role) => role.display_name ?? role.name).join(', ') || '—';
}

function applySearch(value) {
    router.get('/admin/users', value ? { search: value } : {}, { preserveScroll: true });
}

function deactivateUser(userId) {
    router.post(`/admin/users/${userId}/deactivate`, {}, { preserveScroll: true });
}

function rowActions(row) {
    const items = [];
    if (props.canAssignRoles) {
        items.push({ label: 'Manage roles', href: `/admin/users/${row.id}` });
    }
    if (props.canDeactivate && row.is_active) {
        items.push({ label: 'Deactivate', onClick: () => deactivateUser(row.id) });
    }
    return items;
}

function openCreateModal() {
    createForm.reset();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    createForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, { onOpen: openCreateModal });
</script>

<template>
    <AppLayout title="Platform users">
        <PageHeader
            title="Platform users"
            subtitle="S1 identity — create and deactivate staff accounts"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/admin/roles" class="wh-btn-outline">Roles</Link>
                <Link href="/admin/audit-logs" class="wh-btn-outline">Audit log</Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">
                    <Plus class="h-4 w-4" />
                    Create user
                </button>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <DataTable
            list-title="User list"
            :columns="columns"
            :rows="users"
            empty-message="No users found."
            selectable
            searchable
            :search="search"
            search-placeholder="Search"
            :meta="meta"
            :sort-options="sortOptions"
            @search="applySearch"
            @page="(page) => router.get('/admin/users', { search: search || undefined, page }, { preserveScroll: true })"
        >
            <template #cell-username="{ row }">
                <Link :href="`/admin/users/${row.id}`" class="wh-table-link">{{ row.username }}</Link>
            </template>
            <template #cell-roles="{ row }">
                {{ roleNames(row) }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="userStatus(row)" />
            </template>
            <template #cell-actions="{ row }">
                <RowActions v-if="rowActions(row).length" :items="rowActions(row)" />
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal :open="showCreateModal" title="Create platform user" subtitle="User must change password on first login" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                    <input id="username" v-model="createForm.username" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" v-model="createForm.email" type="email" required class="wh-input" />
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Temporary password</label>
                    <input id="password" v-model="createForm.password" type="password" required minlength="10" class="wh-input" />
                </div>
                <div>
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input id="display_name" v-model="createForm.display_name" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="employee_id" class="mb-1 block text-sm font-medium text-slate-700">Employee ID (optional)</label>
                    <input id="employee_id" v-model="createForm.employee_id" type="number" min="1" class="wh-input" />
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">Create user</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
