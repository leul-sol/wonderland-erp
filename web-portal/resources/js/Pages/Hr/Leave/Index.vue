<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    leaveRequests: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
});

const form = useForm({
    employee_id: props.employees[0]?.id ?? '',
    leave_type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
});

const columns = [
    { key: 'request_number', label: 'Request #' },
    { key: 'employee', label: 'Employee' },
    { key: 'leave_type', label: 'Type' },
    { key: 'dates', label: 'Dates' },
    { key: 'days_requested', label: 'Days' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function submit() {
    form.post('/hr/leave-requests');
}

function approve(id) {
    router.post(`/hr/leave-requests/${id}/approve`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Leave requests">
        <PageHeader title="Leave requests" subtitle="Submit and approve employee leave">
            <template #actions>
                <Link href="/hr/employees" class="wh-btn-secondary">Employees</Link>
            </template>
        </PageHeader>

        <section v-if="canCreate" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">New request</h3>
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Employee</label>
                    <select v-model="form.employee_id" required class="wh-input">
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Leave type</label>
                    <select v-model="form.leave_type" required class="wh-input">
                        <option value="annual">Annual</option>
                        <option value="sick">Sick</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="maternity">Maternity</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Start date</label>
                    <input v-model="form.start_date" type="date" required class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">End date</label>
                    <input v-model="form.end_date" type="date" required class="wh-input" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Reason</label>
                    <input v-model="form.reason" type="text" class="wh-input" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="form.processing">Submit request</button>
                </div>
            </form>
        </section>

        <DataTable list-title="Leave request list" selectable :columns="columns" :rows="leaveRequests" empty-message="No leave requests yet.">
            <template #cell-employee="{ row }">
                {{ row.employee?.full_name ?? '—' }}
            </template>
            <template #cell-dates="{ row }">
                {{ row.start_date }} → {{ row.end_date }}
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <button
                    v-if="canApprove && row.status === 'pending'"
                    type="button"
                    class="wh-btn-primary text-xs"
                    @click="approve(row.id)"
                >
                    Approve
                </button>
            </template>
        </DataTable>
    </AppLayout>
</template>
