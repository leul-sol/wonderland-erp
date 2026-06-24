<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    roles: { type: Array, default: () => [] },
    canSyncPermissions: { type: Boolean, default: false },
});

const columns = [
    { key: 'name', label: 'Role' },
    { key: 'display_name', label: 'Label' },
    { key: 'permissions_count', label: 'Permissions', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];
</script>

<template>
    <AppLayout title="Roles">
        <PageHeader title="Roles" subtitle="Assign permission bundles to staff roles">
            <template #actions>
                <Link href="/admin/users" class="wh-btn-secondary">Users</Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="roles" empty-message="No roles configured.">
            <template #cell-actions="{ row }">
                <Link
                    v-if="canSyncPermissions"
                    :href="`/admin/roles/${row.id}`"
                    class="wh-btn-secondary text-xs"
                >
                    Permissions
                </Link>
                <span v-else class="text-xs text-slate-400">Read only</span>
            </template>
        </DataTable>
    </AppLayout>
</template>
