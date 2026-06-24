<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    assignedRoleIds: { type: Array, default: () => [] },
    canAssignRoles: { type: Boolean, default: false },
});

const form = useForm({
    role_ids: [...props.assignedRoleIds],
});

function toggleRole(roleId) {
    const ids = new Set(form.role_ids);
    if (ids.has(roleId)) {
        if (ids.size <= 1) {
            return;
        }
        ids.delete(roleId);
    } else {
        ids.add(roleId);
    }
    form.role_ids = [...ids];
}

function submit() {
    form.post(`/admin/users/${props.user.id}/roles`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="user.display_name ?? user.username">
        <PageHeader
            :title="user.display_name ?? user.username"
            :subtitle="`${user.username} · ${user.email}`"
        >
            <template #actions>
                <Link href="/admin/users" class="wh-btn-secondary">All users</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium text-slate-500">Username</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ user.username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Status</dt>
                    <dd class="mt-1">
                        <StatusBadge :status="user.is_active ? 'active' : 'inactive'" />
                    </dd>
                </div>
                <div v-if="user.employee_id">
                    <dt class="text-xs font-medium text-slate-500">Employee ID</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ user.employee_id }}</dd>
                </div>
            </dl>
        </section>

        <form class="wh-card p-4" @submit.prevent="submit">
            <h2 class="mb-1 text-base font-semibold text-slate-900">Assigned roles</h2>
            <p class="mb-4 text-sm text-slate-500">Select at least one role. Saving replaces the user&apos;s current role set.</p>

            <div v-if="roles.length === 0" class="text-sm text-slate-500">No roles available.</div>
            <div v-else class="grid gap-2 sm:grid-cols-2">
                <label
                    v-for="role in roles"
                    :key="role.id"
                    class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-100 p-3 hover:bg-slate-50"
                >
                    <input
                        type="checkbox"
                        class="mt-1"
                        :checked="form.role_ids.includes(role.id)"
                        :disabled="!canAssignRoles"
                        @change="toggleRole(role.id)"
                    />
                    <span>
                        <span class="block text-sm font-medium text-slate-900">{{ role.display_name }}</span>
                        <span class="block text-xs text-slate-500">{{ role.name }}</span>
                    </span>
                </label>
            </div>

            <div v-if="canAssignRoles" class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing || form.role_ids.length === 0">
                    Save roles
                </button>
            </div>
        </form>
    </AppLayout>
</template>
