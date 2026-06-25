<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    role: { type: Object, required: true },
    permissions: { type: Array, default: () => [] },
    assignedPermissionIds: { type: Array, default: () => [] },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    canSyncPermissions: { type: Boolean, default: false },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const form = useForm({
    permission_ids: [...props.assignedPermissionIds],
});

const originalIds = ref([...props.assignedPermissionIds].sort().join(','));

watch(
    () => props.assignedPermissionIds,
    (ids) => {
        form.permission_ids = [...ids];
        originalIds.value = [...ids].sort().join(',');
    },
);

const isDirty = computed(() => {
    const current = [...form.permission_ids].sort().join(',');

    return current !== originalIds.value;
});

const groupedPermissions = computed(() => {
    const systems = {};

    for (const permission of props.permissions) {
        const system = permissionSystem(permission);
        const domain = permission.domain ?? 'other';

        if (!systems[system]) {
            systems[system] = {};
        }

        if (!systems[system][domain]) {
            systems[system][domain] = [];
        }

        systems[system][domain].push(permission);
    }

    return systems;
});

function permissionSystem(permission) {
    const action = permission.action ?? '';

    if (action.startsWith('S1.')) {
        return 'S1 Identity';
    }

    if (action.startsWith('S2.')) {
        return 'S2 Workforce';
    }

    if (action.startsWith('S3.')) {
        return 'S3 Hospitality';
    }

    if (action.startsWith('S4.')) {
        return 'S4 Finance & BI';
    }

    return 'Other';
}

function togglePermission(permissionId) {
    const ids = new Set(form.permission_ids);

    if (ids.has(permissionId)) {
        ids.delete(permissionId);
    } else {
        ids.add(permissionId);
    }

    form.permission_ids = [...ids];
}

function selectAllInDomain(items) {
    const ids = new Set(form.permission_ids);

    for (const permission of items) {
        ids.add(permission.id);
    }

    form.permission_ids = [...ids];
}

function clearAllInDomain(items) {
    const remove = new Set(items.map((item) => item.id));
    form.permission_ids = form.permission_ids.filter((id) => !remove.has(id));
}

function submit() {
    form.post(`/admin/roles/${props.role.id}/permissions`, {
        preserveScroll: true,
        onSuccess: () => {
            originalIds.value = [...form.permission_ids].sort().join(',');
        },
    });
}

function deleteRole() {
    if (!window.confirm(`Delete role "${props.role.display_name}"?`)) {
        return;
    }

    router.delete(`/admin/roles/${props.role.id}`);
}

function onBeforeUnload(event) {
    if (!isDirty.value) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
}

onMounted(() => {
    window.addEventListener('beforeunload', onBeforeUnload);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
});
</script>

<template>
    <AppLayout :title="role.display_name">
        <PageHeader
            :title="role.display_name"
            :subtitle="`${role.name} · ${role.permissions_count ?? assignedPermissionIds.length} permissions`"
        >
            <template #actions>
                <Link v-if="canUpdate" :href="`/admin/roles/${role.id}/edit`" class="wh-btn-secondary">Edit role</Link>
                <Link href="/admin/roles" class="wh-btn-secondary">All roles</Link>
            </template>
        </PageHeader>

        <div
            v-if="flashSuccess"
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
            {{ flashSuccess }}
        </div>

        <section v-if="role.description" class="wh-card mb-4 p-4 text-sm text-slate-600">
            {{ role.description }}
        </section>

        <section v-if="canDelete && !role.is_system" class="mb-4 flex justify-end">
            <button type="button" class="wh-btn-outline text-red-700" @click="deleteRole">Delete role</button>
        </section>

        <form class="wh-card p-4" @submit.prevent="submit">
            <div v-for="(domains, system) in groupedPermissions" :key="system" class="mb-8 last:mb-0">
                <h2 class="mb-4 border-b border-slate-100 pb-2 text-sm font-bold uppercase tracking-wide text-teal-800">
                    {{ system }}
                </h2>

                <div v-for="(items, domain) in domains" :key="`${system}-${domain}`" class="mb-5 last:mb-0">
                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ domain }}</h3>
                        <div v-if="canSyncPermissions" class="flex gap-2 text-xs">
                            <button type="button" class="text-teal-700 hover:underline" @click="selectAllInDomain(items)">
                                Select all
                            </button>
                            <button type="button" class="text-slate-500 hover:underline" @click="clearAllInDomain(items)">
                                Clear
                            </button>
                        </div>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="permission in items"
                            :key="permission.id"
                            class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-100 p-2 hover:bg-slate-50"
                            :class="form.permission_ids.includes(permission.id) ? 'border-teal-200 bg-teal-50/40' : ''"
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
                                <span class="block font-mono text-[11px] text-slate-500">{{ permission.action }}</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div
                v-if="canSyncPermissions"
                class="sticky bottom-0 -mx-4 flex items-center justify-between gap-3 border-t border-slate-100 bg-white/95 px-4 py-3 backdrop-blur"
            >
                <p class="text-sm" :class="isDirty ? 'font-medium text-amber-700' : 'text-slate-500'">
                    {{ isDirty ? 'Unsaved permission changes' : 'Permissions saved' }}
                </p>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing || !isDirty">
                    Save permissions
                </button>
            </div>
        </form>
    </AppLayout>
</template>
