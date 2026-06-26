<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    payrollRuns: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
});

const columns = [
    { key: 'run_number', label: 'Run #', sortable: true },
    { key: 'period', label: 'Period' },
    { key: 'headcount', label: 'Employees', class: 'text-right' },
    { key: 'total_gross', label: 'Gross', class: 'text-right' },
    { key: 'total_net', label: 'Net', class: 'text-right' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: '', class: 'text-right' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Payroll runs' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function lineCount(run) {
    return run.lines?.length ?? 0;
}
</script>

<template>
    <AppLayout title="Payroll runs">
        <PageHeader
            title="Payroll runs"
            subtitle="Create → submit → approve → lock (immutable payslips)"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/payroll/severance" class="wh-btn-secondary">Severance</Link>
                <Link v-if="canCreate" href="/payroll/runs/create" class="wh-btn-primary">Create run</Link>
            </template>
        </PageHeader>

        <DataTable list-title="Payroll run list" selectable :columns="columns" :rows="payrollRuns" empty-message="No payroll runs yet.">
            <template #cell-run_number="{ row }">
                <Link :href="`/payroll/runs/${row.id}`" class="wh-table-link">{{ row.run_number }}</Link>
            </template>
            <template #cell-period="{ row }">
                {{ row.period_start }} → {{ row.period_end }}
            </template>
            <template #cell-headcount="{ row }">
                <span class="font-mono tabular-nums">{{ lineCount(row) }}</span>
            </template>
            <template #cell-total_gross="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_gross) }}</span>
            </template>
            <template #cell-total_net="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_net) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/payroll/runs/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
