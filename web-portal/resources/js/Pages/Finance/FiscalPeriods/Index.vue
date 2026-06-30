<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    fiscalPeriods: { type: Array, default: () => [] },
    canClose: { type: Boolean, default: false },
    canLock: { type: Boolean, default: false },
    canCreate: { type: Boolean, default: false },
});

const columns = [
    { key: 'year', label: 'Fiscal year' },
    { key: 'period_number', label: 'Period #' },
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

function openNextPeriod() {
    router.post('/finance/fiscal-periods/open-next', {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Fiscal periods">
        <PageHeader title="Fiscal periods" subtitle="Fiscal year (FY) starts in July — period dates are calendar months. FY 2025 period 12 is June 2026.">
            <template #actions>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openNextPeriod">Open next period</button>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="fiscalPeriods">
        <p class="mb-4 text-sm text-slate-600">
            The fiscal year column is the FY label (e.g. FY 2025 runs Jul 2025–Jun 2026). Start/end dates are the actual calendar month for that period.
            Close twice on an open period: first moves to closing, second completes close. Lock only after closed.
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
        </PageDataSection>
    </AppLayout>
</template>
