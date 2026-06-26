<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import LoadErrorBanner from '../../../Components/LoadErrorBanner.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    roles: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canSyncPermissions: { type: Boolean, default: false },
    canBrowsePermissions: { type: Boolean, default: false },
    loadError: { type: String, default: null },
    loadErrorCode: { type: String, default: null },
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
                <Link v-if="canCreate" href="/admin/roles/create" class="wh-btn-primary">New role</Link>
                <Link href="/admin/users" class="wh-btn-secondary">Users</Link>
            </template>
        </PageHeader>

        <LoadErrorBanner v-if="loadError" :message="loadError" :code="loadErrorCode" />

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
    </AppLayout>
</template>
