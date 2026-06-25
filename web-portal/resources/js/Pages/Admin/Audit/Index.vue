<script setup>
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    auditLogs: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    filters: { type: Object, default: () => ({}) },
});

const filterForm = reactive({
    event: props.filters.event ?? '',
    user_id: props.filters.user_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const columns = [
    { key: 'created_at', label: 'When' },
    { key: 'event', label: 'Event' },
    { key: 'user', label: 'User' },
    { key: 'ip_address', label: 'IP' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Administration', href: '/admin/users' },
    { label: 'Audit log' },
];

function applyFilters() {
    router.get('/admin/audit-logs', {
        event: filterForm.event || undefined,
        user_id: filterForm.user_id || undefined,
        from: filterForm.from || undefined,
        to: filterForm.to || undefined,
    }, { preserveScroll: true });
}

function goToPage(page) {
    router.get('/admin/audit-logs', {
        event: filterForm.event || undefined,
        user_id: filterForm.user_id || undefined,
        from: filterForm.from || undefined,
        to: filterForm.to || undefined,
        page,
    }, { preserveScroll: true });
}

function formatUser(row) {
    return row.user?.username ?? (row.user_id ? `#${row.user_id}` : '—');
}
</script>

<template>
    <AppLayout title="Audit log">
        <PageHeader
            title="Audit log"
            subtitle="Platform security and permission change history"
            :breadcrumbs="breadcrumbs"
            :show-export="true"
        >
            <template #actions>
                <Link href="/admin/users" class="wh-btn-outline">Users</Link>
            </template>
        </PageHeader>

        <DataTable
            list-title="Audit entries"
            :columns="columns"
            :rows="auditLogs"
            empty-message="No audit entries match these filters."
            selectable
            searchable
            :meta="meta"
            :sort-options="[
                { label: 'Sort By A-Z', value: 'event_asc' },
                { label: 'Newest first', value: 'newest' },
            ]"
            @page="goToPage"
        >
            <template #filters>
                <form class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="applyFilters">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Event</label>
                        <input v-model="filterForm.event" type="text" class="wh-input" placeholder="user.created" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">User ID</label>
                        <input v-model="filterForm.user_id" type="number" min="1" class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">From</label>
                        <input v-model="filterForm.from" type="date" class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">To</label>
                        <input v-model="filterForm.to" type="date" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button type="submit" class="wh-btn-secondary">Apply filters</button>
                    </div>
                </form>
            </template>

            <template #cell-user="{ row }">
                {{ formatUser(row) }}
            </template>
        </DataTable>
    </AppLayout>
</template>
