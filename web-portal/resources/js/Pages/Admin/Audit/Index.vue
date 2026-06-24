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
        <PageHeader title="Audit log" subtitle="Platform security and permission change history">
            <template #actions>
                <Link href="/admin/users" class="wh-btn-secondary">Users</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
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
        </section>

        <DataTable :columns="columns" :rows="auditLogs" empty-message="No audit entries match these filters.">
            <template #cell-user="{ row }">
                {{ formatUser(row) }}
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
