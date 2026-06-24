<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    role: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },
    assignedPermissionIds: { type: Array, default: () => [] },
    canSyncPermissions: { type: Boolean, default: false },
});

const form = useForm({
    permission_ids: [...props.assignedPermissionIds],
});

const groupedPermissions = computed(() => {
    const groups = {};

    for (const permission of props.permissions) {
        const domain = permission.domain ?? 'other';
        if (!groups[domain]) {
            groups[domain] = [];
        }
        groups[domain].push(permission);
    }

    return groups;
});

function togglePermission(permissionId) {
    const ids = new Set(form.permission_ids);
    if (ids.has(permissionId)) {
        ids.delete(permissionId);
    } else {
        ids.add(permissionId);
    }
    form.permission_ids = [...ids];
}

function submit() {
    form.post(`/admin/roles/${props.role.id}/permissions`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="role.display_name">
        <PageHeader
            :title="role.display_name"
            :subtitle="`${role.name} · ${role.permissions_count ?? assignedPermissionIds.length} permissions`"
        >
            <template #actions>
                <Link href="/admin/roles" class="wh-btn-secondary">All roles</Link>
            </template>
        </PageHeader>

        <form class="wh-card p-4" @submit.prevent="submit">
            <div v-for="(items, domain) in groupedPermissions" :key="domain" class="mb-6 last:mb-0">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ domain }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="permission in items"
                        :key="permission.id"
                        class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-100 p-2 hover:bg-slate-50"
                    >
                        <input
                            type="checkbox"
                            class="mt-1"
                            :checked="form.permission_ids.includes(permission.id)"
                            :disabled="!canSyncPermissions"
                            @change="togglePermission(permission.id)"
                        />
                        <span>
                            <span class="block text-sm font-medium text-slate-900">{{ permission.display_name }}</span>
                            <span class="block text-xs text-slate-500">{{ permission.action }}</span>
                        </span>
                    </label>
                </div>
            </div>
            <div v-if="canSyncPermissions" class="flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save permissions</button>
            </div>
        </form>
    </AppLayout>
</template>
