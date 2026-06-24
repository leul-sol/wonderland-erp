<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    search: { type: String, default: '' },
    canCreate: { type: Boolean, default: false },
    canDeactivate: { type: Boolean, default: false },
});

const columns = [
    { key: 'username', label: 'Username' },
    { key: 'display_name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'roles', label: 'Roles' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function userStatus(user) {
    return user.is_active ? 'active' : 'inactive';
}

function roleNames(user) {
    return (user.roles ?? []).map((role) => role.display_name ?? role.name).join(', ') || '—';
}

function applySearch(event) {
    const value = event.target.value;
    router.get('/admin/users', value ? { search: value } : {}, { preserveScroll: true });
}

function deactivateUser(userId) {
    router.post(`/admin/users/${userId}/deactivate`, {}, { preserveScroll: true });
}

function goToPage(page) {
    router.get('/admin/users', { search: props.search || undefined, page }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Platform users">
        <PageHeader title="Platform users" subtitle="S1 identity — create and deactivate staff accounts">
            <template #actions>
                <Link href="/admin/roles" class="wh-btn-secondary">Roles</Link>
                <Link href="/admin/audit-logs" class="wh-btn-secondary">Audit log</Link>
                <Link v-if="canCreate" href="/admin/users/create" class="wh-btn-primary">Create user</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <label class="mb-1 block text-xs font-medium text-slate-600">Search</label>
            <input
                type="search"
                class="wh-input max-w-md"
                :value="search"
                placeholder="Username, email, or display name"
                @change="applySearch"
            />
        </section>

        <DataTable :columns="columns" :rows="users" empty-message="No users found.">
            <template #cell-roles="{ row }">
                {{ roleNames(row) }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="userStatus(row)" />
            </template>
            <template #cell-actions="{ row }">
                <button
                    v-if="canDeactivate && row.is_active"
                    type="button"
                    class="wh-btn-secondary text-xs"
                    @click="deactivateUser(row.id)"
                >
                    Deactivate
                </button>
            </template>
        </DataTable>

        <nav v-if="meta && meta.last_page > 1" class="mt-4 flex justify-center gap-2">
            <button
                v-for="page in meta.last_page"
                :key="page"
                type="button"
                class="rounded px-3 py-1 text-sm"
                :class="page === meta.current_page ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700'"
                @click="goToPage(page)"
            >
                {{ page }}
            </button>
        </nav>
    </AppLayout>
</template>
