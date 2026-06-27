<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
});

const offboardingRecords = computed(() => props.pageLoad?.offboardingRecords ?? []);
const eligibleEmployees = computed(() => props.pageLoad?.eligibleEmployees ?? []);

const form = useForm({
    employee_id: '',
    reason: 'resignation',
    last_working_day: '',
    notes: '',
    calculate_severance: true,
});

const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'reason', label: 'Reason' },
    { key: 'last_working_day', label: 'Last day' },
    { key: 'clearance_status', label: 'Clearance' },
    { key: 'severance_amount', label: 'Severance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Offboarding' },
];

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = Number.parseFloat(value);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function reasonLabel(reason) {
    return String(reason ?? '').replaceAll('_', ' ');
}

function submit() {
    form.post('/hr/offboarding');
}
</script>

<template>
    <AppLayout title="Offboarding">
        <PageHeader
            title="Offboarding & dead file"
            subtitle="Exit clearance workflow — assets, severance, archive"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/payroll/severance" class="wh-btn-outline">Severance</Link>
                <Link href="/hr/employees" class="wh-btn-secondary">Employees</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <section v-if="canCreate && eligibleEmployees.length" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Start offboarding</h3>
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Employee</label>
                    <select v-model="form.employee_id" required class="wh-input">
                        <option value="" disabled>Select employee</option>
                        <option v-for="employee in eligibleEmployees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Reason</label>
                    <select v-model="form.reason" required class="wh-input">
                        <option value="resignation">Resignation</option>
                        <option value="termination">Termination</option>
                        <option value="retirement">Retirement</option>
                        <option value="end_of_contract">End of contract</option>
                        <option value="death">Death</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Last working day</label>
                    <input v-model="form.last_working_day" type="date" required class="wh-input" />
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Notes</label>
                    <input v-model="form.notes" type="text" class="wh-input" placeholder="Optional HR notes" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input id="calculate_severance" v-model="form.calculate_severance" type="checkbox" class="rounded border-slate-300" />
                    <label for="calculate_severance" class="text-sm text-slate-700">Calculate severance on open</label>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="form.processing">Open dead file</button>
                </div>
            </form>
        </section>

        <p v-else-if="canCreate" class="mb-6 text-sm text-slate-600">All active employees already have an offboarding record.</p>

        <DataTable
            list-title="Offboarding records"
            selectable
            :columns="columns"
            :rows="offboardingRecords"
            empty-message="No offboarding records yet."
        >
            <template #cell-employee_name="{ row }">
                <Link :href="`/hr/offboarding/${row.id}`" class="wh-table-link">{{ row.employee_name ?? '—' }}</Link>
            </template>
            <template #cell-reason="{ row }">
                <span class="capitalize">{{ reasonLabel(row.reason) }}</span>
            </template>
            <template #cell-clearance_status="{ row }">
                <StatusBadge :status="row.clearance_status" />
            </template>
            <template #cell-severance_amount="{ row }">
                <span v-if="row.severance_amount" class="wh-money">ETB {{ formatMoney(row.severance_amount) }}</span>
                <span v-else>—</span>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/hr/offboarding/${row.id}`" class="wh-btn-secondary text-xs">
                    {{ canUpdate && row.clearance_status !== 'completed' ? 'Continue' : 'View' }}
                </Link>
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
