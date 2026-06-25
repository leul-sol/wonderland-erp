<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    journalEntries: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
});

const columns = [
    { key: 'entry_number', label: 'Entry #' },
    { key: 'entry_date', label: 'Date' },
    { key: 'description', label: 'Description' },
    { key: 'total_debit', label: 'Debit', class: 'text-right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];
</script>

<template>
    <AppLayout title="Journal entries">
        <PageHeader title="Manual journals" subtitle="Draft → finance approve → GM (if large) → posted">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
                <Link v-if="canCreate" href="/finance/journals/create" class="wh-btn-primary">New journal</Link>
            </template>
        </PageHeader>

        <DataTable list-title="Journal list" selectable :columns="columns" :rows="journalEntries" empty-message="No manual journal entries yet.">
            <template #cell-total_debit="{ row }">
                <span class="wh-money">{{ row.total_debit }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/finance/journals/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
