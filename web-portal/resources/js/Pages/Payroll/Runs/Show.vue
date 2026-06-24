<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import ApprovalStepper from '../../../Components/ApprovalStepper.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    payrollRun: { type: Object, required: true },
    approvalSteps: { type: Array, default: () => [] },
    approvalCurrentStep: { type: String, default: '' },
    canSubmit: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
});

const submitForm = useForm({});
const approveForm = useForm({});

const lineColumns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'gross_salary', label: 'Gross', class: 'text-right' },
    { key: 'income_tax', label: 'Tax', class: 'text-right' },
    { key: 'employee_pension', label: 'Pension', class: 'text-right' },
    { key: 'net_pay', label: 'Net pay', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function submitRun() {
    submitForm.post(`/payroll/runs/${props.payrollRun.id}/submit`, { preserveScroll: true });
}

function approveRun() {
    approveForm.post(`/payroll/runs/${props.payrollRun.id}/approve`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="payrollRun.run_number">
        <PageHeader
            :title="payrollRun.run_number"
            :subtitle="`${payrollRun.period_start} → ${payrollRun.period_end}`"
        >
            <template #actions>
                <StatusBadge :status="payrollRun.status" />
                <Link href="/payroll/runs" class="wh-btn-secondary text-xs">All runs</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Approval workflow</h3>
            <ApprovalStepper :steps="approvalSteps" :current-key="approvalCurrentStep" />
            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button
                    v-if="canSubmit"
                    type="button"
                    class="wh-btn-secondary"
                    :disabled="submitForm.processing"
                    @click="submitRun"
                >
                    Submit for approval
                </button>
                <button
                    v-if="canApprove"
                    type="button"
                    class="wh-btn-primary"
                    :disabled="approveForm.processing"
                    @click="approveRun"
                >
                    Approve and post
                </button>
            </div>
        </section>

        <section class="wh-card mb-6 grid gap-4 p-4 sm:grid-cols-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Total gross</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(payrollRun.total_gross) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Total net</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(payrollRun.total_net) }}</p>
            </div>
            <div v-if="payrollRun.s4_journal_entry_id">
                <p class="text-xs uppercase tracking-wide text-slate-500">S4 journal</p>
                <p class="mt-1 text-sm font-medium text-teal-800">#{{ payrollRun.s4_journal_entry_id }}</p>
            </div>
        </section>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Payroll lines</h3>
            <DataTable :columns="lineColumns" :rows="payrollRun.lines ?? []">
                <template #cell-gross_salary="{ row }">
                    <span class="wh-money">{{ formatMoney(row.gross_salary) }}</span>
                </template>
                <template #cell-income_tax="{ row }">
                    <span class="wh-money">{{ formatMoney(row.income_tax) }}</span>
                </template>
                <template #cell-employee_pension="{ row }">
                    <span class="wh-money">{{ formatMoney(row.employee_pension) }}</span>
                </template>
                <template #cell-net_pay="{ row }">
                    <span class="wh-money">{{ formatMoney(row.net_pay) }}</span>
                </template>
            </DataTable>
        </section>
    </AppLayout>
</template>
