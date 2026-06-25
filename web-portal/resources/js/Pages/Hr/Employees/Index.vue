<script setup>
import { Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    employees: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
});

const columns = [
    { key: 'employee_number', label: 'ID', sortable: true },
    { key: 'full_name', label: 'Name', sortable: true },
    { key: 'department', label: 'Department', sortable: true },
    { key: 'job_title', label: 'Title' },
    { key: 'base_salary', label: 'Base salary', class: 'text-right' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Action', class: 'text-right w-16' },
];

const sortOptions = [
    { label: 'Sort By A-Z', value: 'name_asc' },
    { label: 'Sort By Z-A', value: 'name_desc' },
    { label: 'Department', value: 'department' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Employees' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Employees">
        <PageHeader
            title="Employees"
            subtitle="Workforce records and platform user provisioning"
            :breadcrumbs="breadcrumbs"
            :show-export="true"
        >
            <template #actions>
                <Link href="/hr/leave-requests" class="wh-btn-outline">Leave</Link>
                <Link href="/hr/attendance" class="wh-btn-outline">Attendance</Link>
                <Link v-if="canCreate" href="/hr/employees/create" class="wh-btn-primary">
                    <Plus class="h-4 w-4" />
                    Add employee
                </Link>
            </template>
        </PageHeader>

        <DataTable
            list-title="Employee list"
            :columns="columns"
            :rows="employees"
            empty-message="No employees yet."
            selectable
            :sort-options="sortOptions"
        >
            <template #cell-employee_number="{ row }">
                <Link :href="`/hr/employees/${row.id}`" class="wh-table-link">{{ row.employee_number }}</Link>
            </template>
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
                <RowActions :items="[{ label: 'Open', href: `/hr/employees/${row.id}` }]" />
            </template>
        </DataTable>
    </AppLayout>
</template>
