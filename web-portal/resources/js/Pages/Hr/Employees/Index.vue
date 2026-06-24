<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    employees: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
});

const columns = [
    { key: 'employee_number', label: 'Number' },
    { key: 'full_name', label: 'Name' },
    { key: 'department', label: 'Department' },
    { key: 'job_title', label: 'Title' },
    { key: 'base_salary', label: 'Base salary', class: 'text-right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Employees">
        <PageHeader title="Employees" subtitle="Workforce records and platform user provisioning">
            <template #actions>
                <Link href="/hr/leave-requests" class="wh-btn-secondary">Leave</Link>
                <Link href="/hr/attendance" class="wh-btn-secondary">Attendance</Link>
                <Link v-if="canCreate" href="/hr/employees/create" class="wh-btn-primary">Add employee</Link>
            </template>
        </PageHeader>

        <DataTable :columns="columns" :rows="employees" empty-message="No employees yet.">
            <template #cell-department="{ row }">
                {{ row.department?.name ?? '—' }}
            </template>
            <template #cell-base_salary="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.base_salary) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/hr/employees/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>
    </AppLayout>
</template>
