<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    periods: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    canWrite: { type: Boolean, default: false },
    defaultPeriodStart: { type: String, required: true },
    defaultPeriodEnd: { type: String, required: true },
});

const openForm = useForm({
    employee_id: props.employees[0]?.id ?? '',
    period_start: props.defaultPeriodStart,
    period_end: props.defaultPeriodEnd,
});

const columns = [
    { key: 'id', label: 'Period #' },
    { key: 'employee_id', label: 'Employee' },
    { key: 'period_start', label: 'From' },
    { key: 'period_end', label: 'To' },
    { key: 'total_amount', label: 'Total', class: 'text-right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function openPeriod() {
    openForm.post('/consumption/periods');
}

function closePeriod(periodId) {
    router.post(`/consumption/periods/${periodId}/close`, {}, { preserveScroll: true });
}

function startMealOrder(periodId) {
    router.post(`/consumption/periods/${periodId}/orders`);
}
</script>

<template>
    <AppLayout title="Staff meals">
        <PageHeader title="Employee meal consumption" subtitle="Open period → meal orders → close period (payroll deduction)">
            <template #actions>
                <Link href="/fb/menu" class="wh-btn-secondary">View menu</Link>
            </template>
        </PageHeader>

        <section v-if="canWrite" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Open period</h3>
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="openPeriod">
                <div>
                    <label for="employee_id" class="mb-1 block text-sm font-medium text-slate-700">Employee</label>
                    <select
                        v-if="employees.length"
                        id="employee_id"
                        v-model="openForm.employee_id"
                        required
                        class="wh-input"
                    >
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name ?? employee.employee_number }} (#{{ employee.id }})
                        </option>
                    </select>
                    <input
                        v-else
                        id="employee_id"
                        v-model="openForm.employee_id"
                        type="number"
                        min="1"
                        required
                        class="wh-input"
                        placeholder="Employee ID"
                    />
                </div>
                <div>
                    <label for="period_start" class="mb-1 block text-sm font-medium text-slate-700">Period start</label>
                    <input id="period_start" v-model="openForm.period_start" type="date" required class="wh-input" />
                </div>
                <div>
                    <label for="period_end" class="mb-1 block text-sm font-medium text-slate-700">Period end</label>
                    <input id="period_end" v-model="openForm.period_end" type="date" required class="wh-input" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary w-full" :disabled="openForm.processing">Open period</button>
                </div>
            </form>
        </section>

        <DataTable :columns="columns" :rows="periods" empty-message="No consumption periods yet.">
            <template #cell-total_amount="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_amount) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <div v-if="row.status === 'open' && canWrite" class="flex justify-end gap-2">
                    <button type="button" class="wh-btn-secondary text-xs" @click="startMealOrder(row.id)">
                        Add meal
                    </button>
                    <button type="button" class="wh-btn-primary text-xs" @click="closePeriod(row.id)">
                        Close period
                    </button>
                </div>
            </template>
        </DataTable>
    </AppLayout>
</template>
