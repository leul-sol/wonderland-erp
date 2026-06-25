<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    calculations: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    canCalculate: { type: Boolean, default: false },
    canPay: { type: Boolean, default: false },
});

const calculateForm = useForm({
    employee_id: props.employees[0]?.id ?? '',
});

const columns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'amount', label: 'Amount', class: 'text-right' },
    { key: 'months_of_service', label: 'Months' },
    { key: 'calculation_date', label: 'Calculated' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function calculate() {
    calculateForm.post('/payroll/severance/calculate', { preserveScroll: true });
}

function pay(id) {
    router.post(`/payroll/severance/${id}/pay`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Severance">
        <PageHeader title="Severance" subtitle="Calculate liability → payout (UAT-S2-004/005)">
            <template #actions>
                <Link href="/payroll/runs" class="wh-btn-secondary">Payroll runs</Link>
            </template>
        </PageHeader>

        <section v-if="canCalculate" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Calculate severance</h3>
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="calculate">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Employee</label>
                    <select v-model="calculateForm.employee_id" required class="wh-input min-w-56">
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                </div>
                <button type="submit" class="wh-btn-primary" :disabled="calculateForm.processing">Calculate</button>
            </form>
        </section>

        <DataTable list-title="Severance list" selectable :columns="columns" :rows="calculations" empty-message="No severance calculations yet.">
            <template #cell-amount="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.amount) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <button
                    v-if="canPay && row.status === 'calculated'"
                    type="button"
                    class="wh-btn-primary text-xs"
                    @click="pay(row.id)"
                >
                    Pay out
                </button>
            </template>
        </DataTable>
    </AppLayout>
</template>
