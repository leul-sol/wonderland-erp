<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    search: { type: String, default: '' },
    canCreate: { type: Boolean, default: false },
    canDeactivate: { type: Boolean, default: false },
    canAssignRoles: { type: Boolean, default: false },
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
</script>

<template>
    <AppLayout title="Platform users">
        <PageHeader
            title="Platform users"
            subtitle="S1 identity — create and deactivate staff accounts"
            :breadcrumbs="breadcrumbs"
            :show-export="true"
        >
            <template #actions>
                <Link href="/admin/roles" class="wh-btn-outline">Roles</Link>
                <Link href="/admin/audit-logs" class="wh-btn-outline">Audit log</Link>
                <Link v-if="canCreate" href="/admin/users/create" class="wh-btn-primary">
                    <Plus class="h-4 w-4" />
                    Create user
                </Link>
            </template>
        </PageHeader>

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
    </AppLayout>
</template>
