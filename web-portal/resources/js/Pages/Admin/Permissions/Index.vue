<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import LoadErrorBanner from '../../../Components/LoadErrorBanner.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    permissions: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    domain: { type: String, default: '' },
    domains: { type: Array, default: () => [] },
    loadError: { type: String, default: null },
    loadErrorCode: { type: String, default: null },
});

const columns = [
    { key: 'action', label: 'Permission' },
    { key: 'display_name', label: 'Label' },
    { key: 'domain', label: 'Domain' },
];

const groupedBySystem = computed(() => {
    const groups = {};

    for (const permission of props.permissions) {
        const system = systemLabel(permission.action ?? '');
        groups[system] = (groups[system] ?? 0) + 1;
    }

    return groups;
});

function systemLabel(action) {
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

function filterDomain(value) {
    router.get('/admin/permissions', value ? { domain: value } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Administration', href: '/admin/users' },
    { label: 'Roles', href: '/admin/roles' },
    { label: 'Permissions' },
];
</script>

<template>
    <AppLayout title="Permission catalog">
        <PageHeader
            title="Permission catalog"
            subtitle="Read-only list of all platform permissions"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/admin/roles" class="wh-btn-secondary">Roles</Link>
            </template>
        </PageHeader>

        <LoadErrorBanner v-if="loadError" :message="loadError" :code="loadErrorCode" />

        <div class="mb-4 flex flex-wrap items-center gap-3">
            <label class="text-sm text-slate-600">
                Domain
                <select
                    class="wh-dash-date-select ml-2 min-w-[10rem]"
                    :value="domain"
                    @change="filterDomain($event.target.value)"
                >
                    <option value="">All domains</option>
                    <option v-for="item in domains" :key="item" :value="item">{{ item }}</option>
                </select>
            </label>
            <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                <span v-for="(count, system) in groupedBySystem" :key="system" class="rounded-full bg-slate-100 px-2 py-0.5">
                    {{ system }}: {{ count }}
                </span>
            </div>
        </div>

        <DataTable
            list-title="Permissions"
            :columns="columns"
            :rows="permissions"
            empty-message="No permissions match this filter."
        >
            <template #cell-action="{ row }">
                <code class="text-xs text-slate-700">{{ row.action }}</code>
            </template>
        </DataTable>

        <p v-if="meta?.total" class="mt-3 text-xs text-slate-500">
            Showing {{ permissions.length }} of {{ meta.total }} permissions
        </p>
    </AppLayout>
</template>
