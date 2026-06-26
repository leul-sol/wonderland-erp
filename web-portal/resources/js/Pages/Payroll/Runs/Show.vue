<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApprovalStepper from '../../../Components/ApprovalStepper.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    payrollRun: { type: Object, required: true },
    approvalSteps: { type: Array, default: () => [] },
    approvalCurrentStep: { type: String, default: '' },
    canSubmit: { type: Boolean, default: false },
    canApprove: { type: Boolean, default: false },
    canLock: { type: Boolean, default: false },
    canReadPayslips: { type: Boolean, default: false },
});

const submitForm = useForm({});
const approveForm = useForm({});
const lockForm = useForm({});

const lines = computed(() => props.payrollRun.lines ?? []);

const lineColumns = [
    { key: 'employee_name', label: 'Employee' },
    { key: 'gross_salary', label: 'Gross', class: 'text-right' },
    { key: 'overtime_pay', label: 'Overtime', class: 'text-right' },
    { key: 'income_tax', label: 'Tax', class: 'text-right' },
    { key: 'employee_pension', label: 'Pension', class: 'text-right' },
    { key: 'loan_repayment', label: 'Loan', class: 'text-right' },
    { key: 'other_deductions', label: 'Other', class: 'text-right' },
    { key: 'net_pay', label: 'Net pay', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right w-28' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'Payroll', href: '/payroll/runs' },
    { label: props.payrollRun.run_number },
];

const totals = computed(() => {
    const sum = (key) =>
        lines.value.reduce((total, line) => total + Number.parseFloat(line[key] ?? 0), 0);

    return {
        headcount: lines.value.length,
        tax: sum('income_tax'),
        employeePension: sum('employee_pension'),
        employerPension: sum('employer_pension'),
        loanRepayment: sum('loan_repayment'),
        overtime: sum('overtime_pay'),
        otherDeductions: sum('other_deductions'),
    };
});

const isFinalized = computed(() => ['approved', 'locked'].includes(props.payrollRun.status));

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function formatDateTime(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

async function submitRun() {
    const confirmed = await confirmAction({
        title: 'Submit for approval',
        message: 'Submit this payroll run for approval?',
        confirmLabel: 'Submit',
    });

    if (!confirmed) {
        return;
    }

    submitForm.post(`/payroll/runs/${props.payrollRun.id}/submit`, { preserveScroll: true });
}

async function approveRun() {
    const confirmed = await confirmAction({
        title: 'Approve payroll run',
        message: 'Approve and post this payroll run to finance? This creates the S4 journal entry.',
        confirmLabel: 'Approve and post',
    });

    if (!confirmed) {
        return;
    }

    approveForm.post(`/payroll/runs/${props.payrollRun.id}/approve`, { preserveScroll: true });
}

async function lockRun() {
    const confirmed = await confirmAction({
        title: 'Lock payroll run',
        message: 'Lock this payroll run? After locking it cannot be changed and payslips are final.',
        confirmLabel: 'Lock run',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    lockForm.post(`/payroll/runs/${props.payrollRun.id}/lock`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="payrollRun.run_number">
        <PageHeader
            :title="payrollRun.run_number"
            :subtitle="`${payrollRun.period_start} → ${payrollRun.period_end}`"
            :breadcrumbs="breadcrumbs"
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
                    :disabled="submitForm.processing || lines.length === 0"
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
                <button
                    v-if="canLock"
                    type="button"
                    class="wh-btn-primary"
                    :disabled="lockForm.processing"
                    @click="lockRun"
                >
                    Lock run
                </button>
            </div>
            <p v-if="canSubmit && lines.length === 0" class="mt-2 text-right text-xs text-amber-700">
                Add active employees with attendance in this period before submitting.
            </p>
        </section>

        <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Employees</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">{{ totals.headcount }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total gross</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(payrollRun.total_gross) }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total net</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(payrollRun.total_net) }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Income tax</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(totals.tax) }}</p>
            </div>
        </section>

        <section class="wh-card mb-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Employee pension</p>
                <p class="wh-money mt-1 text-sm font-medium text-slate-800">ETB {{ formatMoney(totals.employeePension) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Employer pension</p>
                <p class="wh-money mt-1 text-sm font-medium text-slate-800">ETB {{ formatMoney(totals.employerPension) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Loan repayments</p>
                <p class="wh-money mt-1 text-sm font-medium text-slate-800">ETB {{ formatMoney(totals.loanRepayment) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Overtime paid</p>
                <p class="wh-money mt-1 text-sm font-medium text-slate-800">ETB {{ formatMoney(totals.overtime) }}</p>
            </div>
        </section>

        <section v-if="payrollRun.s4_journal_entry_id || payrollRun.approved_at" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Finance posting</h3>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div v-if="payrollRun.s4_journal_entry_id">
                    <dt class="text-xs text-slate-500">S4 journal entry</dt>
                    <dd class="text-sm font-medium text-teal-800">#{{ payrollRun.s4_journal_entry_id }}</dd>
                </div>
                <div v-if="payrollRun.approved_at">
                    <dt class="text-xs text-slate-500">Approved at</dt>
                    <dd class="text-sm font-medium text-slate-800">{{ formatDateTime(payrollRun.approved_at) }}</dd>
                </div>
            </dl>
        </section>

        <section class="wh-card overflow-x-auto p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Payroll lines</h3>
                <p v-if="isFinalized && canReadPayslips" class="text-xs text-slate-500">Download payslips per employee below.</p>
            </div>
            <DataTable :columns="lineColumns" :rows="lines" empty-message="No payroll lines calculated yet.">
                <template #cell-employee_name="{ row }">
                    <Link
                        v-if="row.employee_id"
                        :href="`/hr/employees/${row.employee_id}`"
                        class="wh-table-link"
                    >
                        {{ row.employee_name ?? '—' }}
                    </Link>
                    <span v-else>{{ row.employee_name ?? '—' }}</span>
                </template>
                <template #cell-gross_salary="{ row }">
                    <span class="wh-money">{{ formatMoney(row.gross_salary) }}</span>
                </template>
                <template #cell-overtime_pay="{ row }">
                    <span class="wh-money">{{ formatMoney(row.overtime_pay) }}</span>
                </template>
                <template #cell-income_tax="{ row }">
                    <span class="wh-money">{{ formatMoney(row.income_tax) }}</span>
                </template>
                <template #cell-employee_pension="{ row }">
                    <span class="wh-money">{{ formatMoney(row.employee_pension) }}</span>
                </template>
                <template #cell-loan_repayment="{ row }">
                    <span class="wh-money">{{ formatMoney(row.loan_repayment) }}</span>
                </template>
                <template #cell-other_deductions="{ row }">
                    <span class="wh-money">{{ formatMoney(row.other_deductions) }}</span>
                </template>
                <template #cell-net_pay="{ row }">
                    <span class="wh-money font-medium">{{ formatMoney(row.net_pay) }}</span>
                </template>
                <template #cell-actions="{ row }">
                    <a
                        v-if="canReadPayslips && row.employee_id"
                        :href="`/hr/employees/${row.employee_id}/payslip/${payrollRun.id}`"
                        class="wh-btn-secondary text-xs"
                    >
                        Payslip
                    </a>
                </template>
            </DataTable>
        </section>
    </AppLayout>
</template>
