<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    overtimeRecords: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    overtimeRates: { type: Array, default: () => [] },
    filterStatus: { type: String, default: 'pending' },
    canCreate: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
});

const form = useForm({
    employee_id: props.employees[0]?.id ?? '',
    work_date: '',
    hours: '2',
    category: 'working_day',
});

const statusTabs = [
    { key: 'pending', label: 'Pending approval' },
    { key: 'approved', label: 'Approved' },
    { key: 'paid', label: 'Paid' },
    { key: 'all', label: 'All' },
];

const columns = [
    { key: 'employee', label: 'Employee' },
    { key: 'department', label: 'Department' },
    { key: 'work_date', label: 'Date' },
    { key: 'hours', label: 'Hours', class: 'text-right' },
    { key: 'category', label: 'Category' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Overtime' },
];

function categoryLabel(category) {
    return String(category ?? '').replaceAll('_', ' ');
}

function rateLabel(category) {
    const rate = props.overtimeRates.find((item) => item.category === category);

    return rate ? `${rate.multiplier}x` : '—';
}

function filterByStatus(status) {
    router.get('/hr/overtime', { status }, { preserveScroll: true });
}

function submit() {
    form.post('/hr/overtime', { preserveScroll: true });
}

async function approve(id) {
    const confirmed = await confirmAction({
        title: 'Approve overtime',
        message: 'Approve this overtime record for payroll inclusion?',
        confirmLabel: 'Approve',
    });

    if (!confirmed) {
        return;
    }

    router.post(`/hr/overtime/${id}/approve`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Overtime">
        <PageHeader
            title="Overtime queue"
            subtitle="Submit and approve overtime before payroll runs"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/payroll/runs" class="wh-btn-outline">Payroll runs</Link>
                <Link href="/hr/attendance" class="wh-btn-secondary">Attendance</Link>
            </template>
        </PageHeader>

        <section v-if="overtimeRates.length" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Rate multipliers</h3>
            <div class="flex flex-wrap gap-3 text-sm text-slate-700">
                <span v-for="rate in overtimeRates" :key="rate.id" class="rounded-full bg-slate-100 px-3 py-1">
                    {{ categoryLabel(rate.category) }}: {{ rate.multiplier }}x
                </span>
            </div>
        </section>

        <nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <button
                v-for="tab in statusTabs"
                :key="tab.key"
                type="button"
                class="rounded-lg px-3 py-1.5 text-sm font-medium"
                :class="filterStatus === tab.key ? 'bg-teal-700 text-white' : 'text-slate-600 hover:bg-slate-100'"
                @click="filterByStatus(tab.key)"
            >
                {{ tab.label }}
            </button>
        </nav>

        <section v-if="canCreate" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Log overtime</h3>
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
                    <label class="mb-1 block text-xs font-medium text-slate-600">Work date</label>
                    <input v-model="form.work_date" type="date" required class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Hours</label>
                    <input v-model="form.hours" type="number" step="0.25" min="0.25" max="24" required class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Category</label>
                    <select v-model="form.category" required class="wh-input">
                        <option value="working_day">Working day ({{ rateLabel('working_day') }})</option>
                        <option value="sunday">Sunday ({{ rateLabel('sunday') }})</option>
                        <option value="holiday">Holiday ({{ rateLabel('holiday') }})</option>
                        <option value="night">Night ({{ rateLabel('night') }})</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="form.processing">Submit overtime</button>
                </div>
            </form>
        </section>

        <DataTable
            list-title="Overtime records"
            selectable
            :columns="columns"
            :rows="overtimeRecords"
            :empty-message="filterStatus === 'pending' ? 'No pending overtime records.' : 'No overtime records in this view.'"
        >
            <template #cell-employee="{ row }">
                <Link
                    v-if="row.employee?.id"
                    :href="`/hr/employees/${row.employee.id}`"
                    class="wh-table-link"
                >
                    {{ row.employee.full_name }}
                </Link>
                <span v-else>—</span>
            </template>
            <template #cell-department="{ row }">
                {{ row.employee?.department?.name ?? '—' }}
            </template>
            <template #cell-hours="{ row }">
                <span class="font-mono tabular-nums">{{ row.hours }}</span>
            </template>
            <template #cell-category="{ row }">
                <span class="capitalize">{{ categoryLabel(row.category) }}</span>
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
