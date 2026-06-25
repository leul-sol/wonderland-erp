<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import LoadErrorBanner from '../../../Components/LoadErrorBanner.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    assignedRoleIds: { type: Array, default: () => [] },
    canAssignRoles: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canResetPassword: { type: Boolean, default: false },
    canForceLogout: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canDeactivate: { type: Boolean, default: false },
    canViewAudit: { type: Boolean, default: false },
    auditLogs: { type: Array, default: () => [] },
    auditMeta: { type: Object, default: null },
    auditLoadError: { type: String, default: null },
    auditLoadErrorCode: { type: String, default: null },
});

const page = usePage();
const activeTab = ref(
    new URL(page.url, window.location.origin).searchParams.has('audit_page') ? 'audit' : 'details',
);

const roleForm = useForm({
    role_ids: [...props.assignedRoleIds],
});

const resetForm = useForm({
    password: '',
    password_confirmation: '',
    must_change_password: true,
});

const showResetPassword = ref(false);

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: '/' },
    { label: 'Administration', href: '/admin/users' },
    { label: 'Users', href: '/admin/users' },
    { label: props.user.display_name ?? props.user.username },
]);

const auditColumns = [
    { key: 'created_at', label: 'When' },
    { key: 'event', label: 'Event' },
    { key: 'ip_address', label: 'IP' },
    { key: 'user_agent', label: 'User agent' },
];

function toggleRole(roleId) {
    const ids = new Set(roleForm.role_ids);
    if (ids.has(roleId)) {
        if (ids.size <= 1) {
            return;
        }
        ids.delete(roleId);
    } else {
        ids.add(roleId);
    }
    roleForm.role_ids = [...ids];
}

function submitRoles() {
    roleForm.post(`/admin/users/${props.user.id}/roles`, { preserveScroll: true });
}

function submitResetPassword() {
    resetForm.post(`/admin/users/${props.user.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => {
            resetForm.reset();
            showResetPassword.value = false;
        },
    });
}

function forceLogout() {
    if (!window.confirm('Revoke all active sessions for this user?')) {
        return;
    }

    router.post(`/admin/users/${props.user.id}/force-logout`, {}, { preserveScroll: true });
}

function deleteUser() {
    if (!window.confirm('Delete this user account? This cannot be undone.')) {
        return;
    }

    router.delete(`/admin/users/${props.user.id}`);
}

function deactivateUser() {
    if (!window.confirm('Deactivate this user? They will not be able to sign in.')) {
        return;
    }

    router.post(`/admin/users/${props.user.id}/deactivate`, {}, { preserveScroll: true });
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function goToAuditPage(pageNumber) {
    activeTab.value = 'audit';
    router.get(`/admin/users/${props.user.id}`, { audit_page: pageNumber }, { preserveScroll: true });
}

function switchTab(tab) {
    activeTab.value = tab;
    if (tab === 'details') {
        router.get(`/admin/users/${props.user.id}`, {}, { preserveScroll: true, replace: true });
    }
}
</script>

<template>
    <AppLayout :title="user.display_name ?? user.username">
        <PageHeader
            :title="user.display_name ?? user.username"
            :subtitle="`${user.username} · ${user.email}`"
            :breadcrumbs="breadcrumbs"
            :show-print="false"
        >
            <template #actions>
                <Link v-if="canUpdate" :href="`/admin/users/${user.id}/edit`" class="wh-btn-secondary">
                    Edit user
                </Link>
                <Link href="/admin/users" class="wh-btn-secondary">All users</Link>
            </template>
        </PageHeader>

        <div v-if="canViewAudit" class="mb-4 flex gap-1 border-b border-slate-200">
            <button
                type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === 'details'
                    ? 'border-teal-600 text-teal-700'
                    : 'border-transparent text-slate-500 hover:text-slate-700'"
                @click="switchTab('details')"
            >
                Profile & roles
            </button>
            <button
                type="button"
                class="border-b-2 px-4 py-2 text-sm font-medium transition-colors"
                :class="activeTab === 'audit'
                    ? 'border-teal-600 text-teal-700'
                    : 'border-transparent text-slate-500 hover:text-slate-700'"
                @click="switchTab('audit')"
            >
                Audit trail
            </button>
        </div>

        <template v-if="activeTab === 'details'">
            <section class="wh-card mb-6 p-4">
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Username</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ user.username }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Status</dt>
                        <dd class="mt-1 flex flex-wrap gap-2">
                            <StatusBadge :status="user.is_active ? 'active' : 'inactive'" />
                            <StatusBadge v-if="user.must_change_password" status="pending" />
                            <StatusBadge v-if="user.locked_until" status="locked" />
                        </dd>
                    </div>
                    <div v-if="user.employee_id">
                        <dt class="text-xs font-medium text-slate-500">Employee ID</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ user.employee_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Last login</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ formatDate(user.last_login_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Password changed</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ formatDate(user.password_changed_at) }}</dd>
                    </div>
                </dl>
            </section>

            <section v-if="canResetPassword || canForceLogout || canDeactivate || canDelete" class="wh-card mb-6 p-4">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Account actions</h2>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="canResetPassword"
                        type="button"
                        class="wh-btn-secondary"
                        @click="showResetPassword = !showResetPassword"
                    >
                        Reset password
                    </button>
                    <button v-if="canForceLogout" type="button" class="wh-btn-secondary" @click="forceLogout">
                        Force logout
                    </button>
                    <button
                        v-if="canDeactivate && user.is_active"
                        type="button"
                        class="wh-btn-secondary"
                        @click="deactivateUser"
                    >
                        Deactivate
                    </button>
                    <button v-if="canDelete" type="button" class="wh-btn-outline text-red-700" @click="deleteUser">
                        Delete user
                    </button>
                </div>

                <form
                    v-if="showResetPassword && canResetPassword"
                    class="mt-4 grid max-w-md gap-3 border-t border-slate-100 pt-4"
                    @submit.prevent="submitResetPassword"
                >
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                        <input v-model="resetForm.password" type="password" class="wh-input" required />
                        <p v-if="resetForm.errors.password" class="mt-1 text-sm text-red-600">{{ resetForm.errors.password }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Confirm password</label>
                        <input v-model="resetForm.password_confirmation" type="password" class="wh-input" required />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="resetForm.must_change_password" type="checkbox" class="rounded border-slate-300" />
                        Require password change on next login
                    </label>
                    <div>
                        <button type="submit" class="wh-btn-primary" :disabled="resetForm.processing">
                            Set new password
                        </button>
                    </div>
                </form>
            </section>

            <form class="wh-card p-4" @submit.prevent="submitRoles">
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
                            :checked="roleForm.role_ids.includes(role.id)"
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
                    <button type="submit" class="wh-btn-primary" :disabled="roleForm.processing || roleForm.role_ids.length === 0">
                        Save roles
                    </button>
                </div>
            </form>
        </template>

        <template v-else-if="activeTab === 'audit'">
            <LoadErrorBanner
                v-if="auditLoadError"
                :message="auditLoadError"
                :code="auditLoadErrorCode"
            />

            <DataTable
                v-else
                list-title="User audit trail"
                :columns="auditColumns"
                :rows="auditLogs"
                empty-message="No audit entries recorded for this user."
                :meta="auditMeta"
                @page="goToAuditPage"
            >
                <template #cell-created_at="{ row }">
                    {{ formatDate(row.created_at) }}
                </template>
                <template #cell-user_agent="{ row }">
                    <span class="line-clamp-2 text-xs text-slate-600" :title="row.user_agent">{{ row.user_agent ?? '—' }}</span>
                </template>
            </DataTable>
        </template>
    </AppLayout>
</template>
