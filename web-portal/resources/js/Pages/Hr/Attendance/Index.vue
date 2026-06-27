<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    filterDate: { type: String, required: true },
    canCreate: { type: Boolean, default: false },
});

const records = computed(() => props.pageLoad?.records ?? []);
const employees = computed(() => props.pageLoad?.employees ?? []);

const dateFilter = ref(props.filterDate);

const form = useForm({
    employee_id: '',
    work_date: props.filterDate,
    check_in: '08:00',
    check_out: '17:00',
    hours_worked: '8',
    status: 'present',
    notes: '',
});

const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'work_date', label: 'Date' },
    { key: 'check_in', label: 'In' },
    { key: 'check_out', label: 'Out' },
    { key: 'hours_worked', label: 'Hours', class: 'text-right' },
    { key: 'status', label: 'Status' },
];

function submit() {
    form.post('/hr/attendance', { preserveScroll: true });
}

function filterByDate() {
    router.get('/hr/attendance', { work_date: dateFilter.value }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Attendance">
        <PageHeader title="Attendance" subtitle="Daily presence records for payroll">
            <template #actions>
                <Link href="/hr/employees" class="wh-btn-secondary">Employees</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <section class="wh-card mb-6 p-4">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="filterByDate">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Filter by date</label>
                    <input v-model="dateFilter" type="date" class="wh-input" @change="filterByDate" />
                </div>
            </form>
        </section>

        <section v-if="canCreate" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Record attendance</h3>
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submit">
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
                    <label class="mb-1 block text-xs font-medium text-slate-600">Check in</label>
                    <input v-model="form.check_in" type="time" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Check out</label>
                    <input v-model="form.check_out" type="time" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Hours</label>
                    <input v-model="form.hours_worked" type="number" step="0.25" min="0" max="24" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                    <select v-model="form.status" class="wh-input">
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                        <option value="leave">Leave</option>
                        <option value="half_day">Half day</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Notes</label>
                    <input v-model="form.notes" type="text" class="wh-input" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save record</button>
                </div>
            </form>
        </section>

        <DataTable list-title="Attendance list" selectable :columns="columns" :rows="records" empty-message="No attendance records for this date.">
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
