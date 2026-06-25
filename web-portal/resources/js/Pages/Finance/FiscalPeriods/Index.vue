<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    fiscalPeriods: { type: Array, default: () => [] },
    canClose: { type: Boolean, default: false },
    canLock: { type: Boolean, default: false },
});

const columns = [
    { key: 'year', label: 'Year' },
    { key: 'period_number', label: 'Period' },
    { key: 'start_date', label: 'Start' },
    { key: 'end_date', label: 'End' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function closePeriod(id) {
    router.post(`/finance/fiscal-periods/${id}/close`, {}, { preserveScroll: true });
}

function lockPeriod(id) {
    router.post(`/finance/fiscal-periods/${id}/lock`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Fiscal periods">
        <PageHeader title="Fiscal periods" subtitle="Two-step close (open → closing → closed) then lock">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
            </template>
        </PageHeader>

        <p class="mb-4 text-sm text-slate-600">
            Click close twice on an open period: first moves to closing, second completes close. Lock only after closed.
        </p>

        <DataTable list-title="Fiscal period list" selectable :columns="columns" :rows="fiscalPeriods" empty-message="No fiscal periods configured.">
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <div class="flex justify-end gap-2">
                    <button
                        v-if="canClose && (row.status === 'open' || row.status === 'closing')"
                        type="button"
                        class="wh-btn-secondary text-xs"
                        @click="closePeriod(row.id)"
                    >
                        Close
                    </button>
                    <button
                        v-if="canLock && row.status === 'closed'"
                        type="button"
                        class="wh-btn-primary text-xs"
                        @click="lockPeriod(row.id)"
                    >
                        Lock
                    </button>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>
