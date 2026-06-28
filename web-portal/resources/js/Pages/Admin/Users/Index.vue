<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import PasswordField from '../../../Components/PasswordField.vue';
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

const emptyCreateForm = {
    username: '',
    email: '',
    password: '',
    display_name: '',
    employee_id: '',
};

const createForm = useForm({ ...emptyCreateForm });

const createFormError = computed(() => createForm.errors.form ?? null);

function resetCreateForm() {
    createForm.defaults({ ...emptyCreateForm });
    createForm.reset();
    createForm.clearErrors();
}

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
    resetCreateForm();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    resetCreateForm();
}

function submitCreate() {
    createForm.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
        onError: () => {
            showCreateModal.value = true;
        },
    });
}

function fieldInvalid(key) {
    return Boolean(createForm.errors[key]);
}

watch(
    () => createForm.errors,
    (errors) => {
        if (Object.keys(errors).length > 0) {
            showCreateModal.value = true;
        }
    },
    { deep: true },
);

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

        <FormModal
            :open="showCreateModal"
            title="Create platform user"
            subtitle="User must change password on first login"
            :close-on-backdrop="false"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div
                    v-if="createFormError"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                    role="alert"
                >
                    {{ createFormError }}
                </div>

                <div>
                    <label for="username" class="mb-1 block text-sm font-medium text-slate-700">
                        Username <span class="text-red-600">*</span>
                    </label>
                    <input
                        id="username"
                        v-model="createForm.username"
                        type="text"
                        required
                        class="wh-input"
                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-100': fieldInvalid('username') }"
                    />
                    <p v-if="createForm.errors.username" class="mt-1 text-sm text-red-600">{{ createForm.errors.username }}</p>
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">
                        Email <span class="text-red-600">*</span>
                    </label>
                    <input
                        id="email"
                        v-model="createForm.email"
                        type="email"
                        required
                        class="wh-input"
                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-100': fieldInvalid('email') }"
                    />
                    <p v-if="createForm.errors.email" class="mt-1 text-sm text-red-600">{{ createForm.errors.email }}</p>
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">
                        Temporary password <span class="text-red-600">*</span>
                    </label>
                    <PasswordField
                        id="password"
                        v-model="createForm.password"
                        required
                        :minlength="10"
                        :invalid="fieldInvalid('password')"
                    />
                    <p v-if="createForm.errors.password" class="mt-1 text-sm text-red-600">{{ createForm.errors.password }}</p>
                </div>

                <div>
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input
                        id="display_name"
                        v-model="createForm.display_name"
                        type="text"
                        class="wh-input"
                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-100': fieldInvalid('display_name') }"
                    />
                    <p v-if="createForm.errors.display_name" class="mt-1 text-sm text-red-600">{{ createForm.errors.display_name }}</p>
                </div>

                <div>
                    <label for="employee_id" class="mb-1 block text-sm font-medium text-slate-700">Employee ID (optional)</label>
                    <input
                        id="employee_id"
                        v-model="createForm.employee_id"
                        type="number"
                        min="1"
                        class="wh-input"
                        :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-100': fieldInvalid('employee_id') }"
                    />
                    <p v-if="createForm.errors.employee_id" class="mt-1 text-sm text-red-600">{{ createForm.errors.employee_id }}</p>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">
                        {{ createForm.processing ? 'Creating...' : 'Create user' }}
                    </button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
