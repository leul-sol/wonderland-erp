<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import EmployeeFormFields from '../../../Components/Hr/EmployeeFormFields.vue';
import FormModal from '../../../Components/FormModal.vue';
import LoadErrorBanner from '../../../Components/LoadErrorBanner.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    employee: { type: Object, required: true },
    platformUser: { type: Object, default: null },
    activeTab: { type: String, default: 'profile' },
    tabLoadError: { type: String, default: null },
    leaveBalances: { type: Array, default: () => [] },
    employeeLeaveRequests: { type: Array, default: () => [] },
    disciplinaryRecords: { type: Array, default: () => [] },
    employeeAssets: { type: Array, default: () => [] },
    guarantors: { type: Array, default: () => [] },
    loans: { type: Array, default: () => [] },
    payslipRuns: { type: Array, default: () => [] },
    assetTypes: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    canUpdate: { type: Boolean, default: false },
    canViewLeave: { type: Boolean, default: false },
    canWriteDisciplinary: { type: Boolean, default: false },
    canReadDisciplinary: { type: Boolean, default: false },
    canWriteAssets: { type: Boolean, default: false },
    canReadAssets: { type: Boolean, default: false },
    canWriteGuarantors: { type: Boolean, default: false },
    canReadGuarantors: { type: Boolean, default: false },
    canWriteLoans: { type: Boolean, default: false },
    canReadLoans: { type: Boolean, default: false },
    canReadPayslips: { type: Boolean, default: false },
});

const tabs = computed(() => {
    const items = [{ id: 'profile', label: 'Profile' }];

    if (props.canViewLeave) {
        items.push({ id: 'leave', label: 'Leave' });
    }
    if (props.canReadDisciplinary || props.canWriteDisciplinary) {
        items.push({ id: 'disciplinary', label: 'Disciplinary' });
    }
    if (props.canReadAssets || props.canWriteAssets) {
        items.push({ id: 'assets', label: 'Assets' });
    }
    if (props.canReadGuarantors || props.canWriteGuarantors) {
        items.push({ id: 'guarantors', label: 'Guarantors' });
    }
    if (props.canReadLoans || props.canWriteLoans) {
        items.push({ id: 'loans', label: 'Loans' });
    }
    if (props.canReadPayslips) {
        items.push({ id: 'payslips', label: 'Payslips' });
    }
    items.push({ id: 'platform', label: 'Platform user' });

    return items;
});

const disciplinaryForm = useForm({
    action_type: 'oral_warning',
    reason: '',
    effective_date: new Date().toISOString().slice(0, 10),
    suspension_days: '',
});

const assetForm = useForm({
    asset_type_id: props.assetTypes[0]?.id ?? '',
    serial_number: '',
    assigned_date: new Date().toISOString().slice(0, 10),
});

const guarantorForm = useForm({
    full_name: '',
    national_id: '',
    phone: '',
    address: '',
    relationship: '',
});

const loanForm = useForm({
    principal_amount: '',
    monthly_repayment: '',
    disbursed_at: new Date().toISOString().slice(0, 10),
});

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function selectTab(tabId) {
    router.get(`/hr/employees/${props.employee.id}`, { tab: tabId }, { preserveState: true, preserveScroll: true });
}

function submitDisciplinary() {
    disciplinaryForm.post(`/hr/employees/${props.employee.id}/disciplinary-records`, {
        preserveScroll: true,
        onSuccess: () => disciplinaryForm.reset('reason', 'suspension_days'),
    });
}

function submitAsset() {
    assetForm.post(`/hr/employees/${props.employee.id}/assets`, {
        preserveScroll: true,
        onSuccess: () => assetForm.reset('serial_number'),
    });
}

function returnAsset(assetId) {
    router.put(`/hr/employees/${props.employee.id}/assets/${assetId}/return`, {
        returned_date: new Date().toISOString().slice(0, 10),
        condition_on_return: 'Good',
    }, { preserveScroll: true });
}

function submitGuarantor() {
    guarantorForm.post(`/hr/employees/${props.employee.id}/guarantors`, {
        preserveScroll: true,
        onSuccess: () => guarantorForm.reset(),
    });
}

function submitLoan() {
    loanForm.post(`/hr/employees/${props.employee.id}/loans`, {
        preserveScroll: true,
        onSuccess: () => loanForm.reset(),
    });
}

function formatActionType(value) {
    return (value ?? '').replaceAll('_', ' ');
}

const showEditModal = ref(false);

const editForm = useForm({
    full_name: props.employee.full_name ?? '',
    email: props.employee.email ?? '',
    department_id: props.employee.department?.id ?? '',
    position_id: props.employee.position?.id ?? '',
    job_title: props.employee.job_title ?? '',
    base_salary: props.employee.base_salary ?? '',
    pension_category: props.employee.pension_category ?? 'covered',
    default_role: props.employee.default_role ?? 'report_viewer',
});

function openEditModal() {
    editForm.full_name = props.employee.full_name ?? '';
    editForm.email = props.employee.email ?? '';
    editForm.department_id = props.employee.department?.id ?? '';
    editForm.position_id = props.employee.position?.id ?? '';
    editForm.job_title = props.employee.job_title ?? '';
    editForm.base_salary = props.employee.base_salary ?? '';
    editForm.pension_category = props.employee.pension_category ?? 'covered';
    editForm.default_role = props.employee.default_role ?? 'report_viewer';
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
}

function submitEdit() {
    editForm.patch(`/hr/employees/${props.employee.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

useQueryModal(showEditModal, {
    expected: 'edit',
    when: () => props.canUpdate,
    onOpen: openEditModal,
});
</script>

<template>
    <AppLayout :title="employee.full_name">
        <PageHeader
            :title="employee.full_name"
            :subtitle="`${employee.employee_number} · ${employee.department?.name ?? 'No department'}`"
        >
            <template #actions>
                <StatusBadge :status="employee.status" />
                <button v-if="canUpdate" type="button" class="wh-btn-outline" @click="openEditModal">Edit</button>
                <Link href="/hr/employees" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <LoadErrorBanner
            v-if="tabLoadError"
            :message="tabLoadError"
            code="SERVICE_UNAVAILABLE"
            class="mb-4"
        />

        <nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                :class="activeTab === tab.id ? 'bg-teal-700 text-white' : 'text-slate-600 hover:bg-slate-100'"
                @click="selectTab(tab.id)"
            >
                {{ tab.label }}
            </button>
        </nav>

        <div v-if="activeTab === 'profile'" class="grid gap-6 lg:grid-cols-2">
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Employment</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Job title</dt>
                        <dd class="font-medium text-slate-900">{{ employee.job_title ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Position</dt>
                        <dd class="font-medium text-slate-900">{{ employee.position?.title ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Base salary</dt>
                        <dd class="wh-money font-medium text-slate-900">ETB {{ formatMoney(employee.base_salary) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Hire date</dt>
                        <dd class="font-medium text-slate-900">{{ employee.hire_date ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Pension</dt>
                        <dd class="font-medium capitalize text-slate-900">{{ employee.pension_category?.replaceAll('_', ' ') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-medium text-slate-900">{{ employee.email ?? '—' }}</dd>
                    </div>
                </dl>
            </section>
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Quick links</h3>
                <div class="flex flex-wrap gap-2">
                    <Link href="/hr/leave-requests" class="wh-btn-outline text-sm">Leave inbox</Link>
                    <Link href="/hr/attendance" class="wh-btn-outline text-sm">Attendance</Link>
                    <Link href="/payroll/runs" class="wh-btn-outline text-sm">Payroll runs</Link>
                </div>
            </section>
        </div>

        <div v-else-if="activeTab === 'leave' && canViewLeave" class="space-y-6">
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Leave balances</h3>
                <DataTable
                    v-if="leaveBalances.length"
                    list-title=""
                    :columns="[
                        { key: 'type', label: 'Type' },
                        { key: 'year', label: 'Year' },
                        { key: 'accrued', label: 'Accrued' },
                        { key: 'used', label: 'Used' },
                        { key: 'remaining', label: 'Remaining' },
                    ]"
                    :rows="leaveBalances"
                    empty-message="No leave balances."
                >
                    <template #cell-type="{ row }">
                        {{ row.leave_type?.name ?? row.leave_type?.code ?? '—' }}
                    </template>
                    <template #cell-accrued="{ row }">{{ row.days_accrued }}</template>
                    <template #cell-used="{ row }">{{ row.days_used }}</template>
                    <template #cell-remaining="{ row }">{{ row.days_remaining }}</template>
                </DataTable>
                <p v-else class="text-sm text-slate-600">No leave balances on file.</p>
            </section>
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Leave history</h3>
                <DataTable
                    :columns="[
                        { key: 'request_number', label: 'Request #' },
                        { key: 'leave_type', label: 'Type' },
                        { key: 'dates', label: 'Dates' },
                        { key: 'days_requested', label: 'Days' },
                        { key: 'status', label: 'Status' },
                    ]"
                    :rows="employeeLeaveRequests"
                    empty-message="No leave requests for this employee."
                >
                    <template #cell-leave_type="{ row }">{{ row.leave_type ?? '—' }}</template>
                    <template #cell-dates="{ row }">{{ row.start_date }} → {{ row.end_date }}</template>
                    <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
                </DataTable>
            </section>
        </div>

        <div v-else-if="activeTab === 'disciplinary'" class="space-y-6">
            <section v-if="canWriteDisciplinary" class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Record disciplinary action</h3>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitDisciplinary">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Action type</label>
                        <select v-model="disciplinaryForm.action_type" class="wh-input" required>
                            <option value="oral_warning">Oral warning</option>
                            <option value="first_written_warning">First written warning</option>
                            <option value="final_written_warning">Final written warning</option>
                            <option value="suspension">Suspension</option>
                            <option value="termination">Termination</option>
                            <option value="immediate_dismissal">Immediate dismissal</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Effective date</label>
                        <input v-model="disciplinaryForm.effective_date" type="date" required class="wh-input" />
                    </div>
                    <div v-if="disciplinaryForm.action_type === 'suspension'">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Suspension days</label>
                        <input v-model="disciplinaryForm.suspension_days" type="number" min="1" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Reason</label>
                        <textarea v-model="disciplinaryForm.reason" rows="3" required class="wh-input" />
                    </div>
                    <div>
                        <button type="submit" class="wh-btn-primary" :disabled="disciplinaryForm.processing">Add record</button>
                    </div>
                </form>
            </section>
            <DataTable
                list-title="Disciplinary history"
                :columns="[
                    { key: 'effective_date', label: 'Date' },
                    { key: 'action_type', label: 'Action' },
                    { key: 'reason', label: 'Reason' },
                ]"
                :rows="disciplinaryRecords"
                empty-message="No disciplinary records."
            >
                <template #cell-action_type="{ row }">{{ formatActionType(row.action_type) }}</template>
            </DataTable>
        </div>

        <div v-else-if="activeTab === 'assets'" class="space-y-6">
            <section v-if="canWriteAssets" class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Assign asset</h3>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitAsset">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Asset type</label>
                        <select v-model="assetForm.asset_type_id" required class="wh-input">
                            <option v-for="type in assetTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Serial number</label>
                        <input v-model="assetForm.serial_number" type="text" class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Assigned date</label>
                        <input v-model="assetForm.assigned_date" type="date" class="wh-input" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="wh-btn-primary" :disabled="assetForm.processing">Assign</button>
                    </div>
                </form>
            </section>
            <DataTable
                list-title="Assigned assets"
                :columns="[
                    { key: 'asset_type', label: 'Type' },
                    { key: 'serial_number', label: 'Serial' },
                    { key: 'assigned_date', label: 'Assigned' },
                    { key: 'returned_date', label: 'Returned' },
                    { key: 'actions', label: '', class: 'text-right' },
                ]"
                :rows="employeeAssets"
                empty-message="No assets assigned."
            >
                <template #cell-asset_type="{ row }">{{ row.asset_type?.name ?? '—' }}</template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="canWriteAssets && !row.returned_date"
                        type="button"
                        class="wh-btn-outline text-xs"
                        @click="returnAsset(row.id)"
                    >
                        Mark returned
                    </button>
                </template>
            </DataTable>
        </div>

        <div v-else-if="activeTab === 'guarantors'" class="space-y-6">
            <section v-if="canWriteGuarantors" class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Register guarantor</h3>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitGuarantor">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Full name</label>
                        <input v-model="guarantorForm.full_name" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">National ID</label>
                        <input v-model="guarantorForm.national_id" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Phone</label>
                        <input v-model="guarantorForm.phone" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Relationship</label>
                        <input v-model="guarantorForm.relationship" type="text" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Address</label>
                        <input v-model="guarantorForm.address" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <button type="submit" class="wh-btn-primary" :disabled="guarantorForm.processing">Register</button>
                    </div>
                </form>
            </section>
            <DataTable
                list-title="Guarantors"
                :columns="[
                    { key: 'full_name', label: 'Name' },
                    { key: 'national_id', label: 'National ID' },
                    { key: 'phone', label: 'Phone' },
                    { key: 'relationship', label: 'Relationship' },
                    { key: 'actions', label: '', class: 'text-right' },
                ]"
                :rows="guarantors"
                empty-message="No guarantors registered."
            >
                <template #cell-actions="{ row }">
                    <a
                        v-if="row.letter_path"
                        :href="`/hr/employees/${employee.id}/guarantors/${row.id}/letter`"
                        class="wh-btn-outline text-xs"
                        target="_blank"
                        rel="noopener"
                    >
                        Download letter
                    </a>
                </template>
            </DataTable>
        </div>

        <div v-else-if="activeTab === 'loans'" class="space-y-6">
            <section v-if="canWriteLoans" class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Disburse staff loan</h3>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitLoan">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Principal (ETB)</label>
                        <input v-model="loanForm.principal_amount" type="number" step="0.01" min="1" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Monthly repayment (ETB)</label>
                        <input v-model="loanForm.monthly_repayment" type="number" step="0.01" min="1" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Disbursed date</label>
                        <input v-model="loanForm.disbursed_at" type="date" class="wh-input" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="wh-btn-primary" :disabled="loanForm.processing">Disburse loan</button>
                    </div>
                </form>
            </section>
            <DataTable
                list-title="Loan history"
                :columns="[
                    { key: 'principal_amount', label: 'Principal' },
                    { key: 'monthly_repayment', label: 'Monthly' },
                    { key: 'remaining_balance', label: 'Balance' },
                    { key: 'status', label: 'Status' },
                    { key: 'disbursed_at', label: 'Disbursed' },
                ]"
                :rows="loans"
                empty-message="No loans on file."
            >
                <template #cell-principal_amount="{ row }">ETB {{ formatMoney(row.principal_amount) }}</template>
                <template #cell-monthly_repayment="{ row }">ETB {{ formatMoney(row.monthly_repayment) }}</template>
                <template #cell-remaining_balance="{ row }">ETB {{ formatMoney(row.remaining_balance) }}</template>
                <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
            </DataTable>
        </div>

        <div v-else-if="activeTab === 'payslips' && canReadPayslips" class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Payslip downloads</h3>
            <p class="mb-4 text-sm text-slate-600">Download PDF payslips for approved or locked payroll runs.</p>
            <ul v-if="payslipRuns.length" class="divide-y divide-slate-100">
                <li v-for="run in payslipRuns" :key="run.id" class="flex items-center justify-between py-3 text-sm">
                    <div>
                        <p class="font-medium text-slate-900">{{ run.run_number ?? `Run #${run.id}` }}</p>
                        <p class="text-slate-500">{{ run.period_start }} → {{ run.period_end }} · {{ run.status }}</p>
                    </div>
                    <a
                        :href="`/hr/employees/${employee.id}/payslip/${run.id}`"
                        class="wh-btn-outline text-xs"
                        target="_blank"
                        rel="noopener"
                    >
                        Download PDF
                    </a>
                </li>
            </ul>
            <p v-else class="text-sm text-slate-600">No payslips for this employee in approved payroll runs yet.</p>
        </div>

        <div v-else-if="activeTab === 'platform'" class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Platform user (S1)</h3>
            <div v-if="platformUser" class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm">
                <p class="font-medium text-teal-900">{{ platformUser.display_name }}</p>
                <p class="mt-1 text-teal-800">Username: {{ platformUser.username }}</p>
                <p class="text-teal-800">Email: {{ platformUser.email }}</p>
                <p class="mt-2 text-xs text-teal-700">User ID #{{ platformUser.id }} · read-only link</p>
            </div>
            <p v-else class="text-sm text-slate-600">
                No platform user linked yet. Provisioning runs via the employee-created event — refresh in a moment.
            </p>
        </div>

        <FormModal
            v-if="canUpdate"
            :open="showEditModal"
            :title="`Edit ${employee.full_name}`"
            :subtitle="employee.employee_number"
            size="lg"
            @close="closeEditModal"
        >
            <form @submit.prevent="submitEdit">
                <EmployeeFormFields
                    :form="editForm"
                    :departments="departments"
                    :positions="positions"
                />
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeEditModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="editForm.processing" @click="submitEdit">Save changes</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
