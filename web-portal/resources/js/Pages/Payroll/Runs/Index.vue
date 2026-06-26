<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    payrollRuns: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    defaultPeriodStart: { type: String, default: '' },
    defaultPeriodEnd: { type: String, default: '' },
    maxPeriodEnd: { type: String, default: '' },
    canRecordAttendance: { type: Boolean, default: false },
});

const page = usePage();
const showCreateModal = ref(false);

const form = useForm({
    period_start: props.defaultPeriodStart,
    period_end: props.defaultPeriodEnd,
});

const attendanceGap = computed(() => page.props.flash?.attendanceGap ?? null);
const periodEndInFuture = computed(() => form.period_end > props.maxPeriodEnd);

const attendanceFixHref = computed(() => {
    if (!attendanceGap.value?.work_date) {
        return '/hr/attendance';
    }

    return `/hr/attendance?work_date=${attendanceGap.value.work_date}`;
});

const columns = [
    { key: 'run_number', label: 'Run #', sortable: true },
    { key: 'period', label: 'Period' },
    { key: 'headcount', label: 'Employees', class: 'text-right' },
    { key: 'total_gross', label: 'Gross', class: 'text-right' },
    { key: 'total_net', label: 'Net', class: 'text-right' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function lineCount(run) {
    return run.lines?.length ?? 0;
}

function openCreateModal() {
    form.reset();
    form.period_start = props.defaultPeriodStart;
    form.period_end = props.defaultPeriodEnd;
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/payroll/runs', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, { onOpen: openCreateModal });
</script>

<template>
    <AppLayout title="Payroll runs">
        <PageHeader title="Payroll runs" subtitle="Create → submit → approve → lock (immutable payslips)">
            <template #actions>
                <Link href="/payroll/severance" class="wh-btn-secondary">Severance</Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">Create run</button>
            </template>
        </PageHeader>

        <DataTable list-title="Payroll run list" selectable :columns="columns" :rows="payrollRuns" empty-message="No payroll runs yet.">
            <template #empty>
                <p>No payroll runs yet.</p>
                <button v-if="canCreate" type="button" class="wh-btn-primary mt-3" @click="openCreateModal">Create your first run</button>
            </template>
            <template #cell-run_number="{ row }">
                <Link :href="`/payroll/runs/${row.id}`" class="wh-table-link">{{ row.run_number }}</Link>
            </template>
            <template #cell-period="{ row }">
                {{ row.period_start }} → {{ row.period_end }}
            </template>
            <template #cell-headcount="{ row }">
                <span class="font-mono tabular-nums">{{ lineCount(row) }}</span>
            </template>
            <template #cell-total_gross="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_gross) }}</span>
            </template>
            <template #cell-total_net="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_net) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/payroll/runs/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>

        <FormModal :open="showCreateModal" title="Create payroll run" subtitle="Generates draft lines for all active employees" @close="closeCreateModal">
            <section class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                <p class="font-medium text-slate-800">Before you create a run</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li>Every active employee needs weekday attendance for each day in the period.</li>
                    <li>Period end cannot be in the future.</li>
                </ul>
                <p v-if="canRecordAttendance" class="mt-2">
                    <Link href="/hr/attendance" class="wh-table-link">Open attendance</Link>
                    to fill gaps first.
                </p>
            </section>

            <section
                v-if="attendanceGap"
                class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                <p class="font-medium">Missing attendance blocked this run</p>
                <p class="mt-1">
                    Record attendance for <strong>{{ attendanceGap.employee_name }}</strong> on
                    <strong>{{ attendanceGap.work_date }}</strong>, then try again.
                </p>
                <Link v-if="canRecordAttendance" :href="attendanceFixHref" class="mt-3 inline-block wh-btn-secondary text-xs">
                    Record attendance for this date
                </Link>
            </section>

            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="period_start" class="mb-1 block text-sm font-medium text-slate-700">Period start</label>
                        <input id="period_start" v-model="form.period_start" type="date" required class="wh-input" />
                    </div>
                    <div>
                        <label for="period_end" class="mb-1 block text-sm font-medium text-slate-700">Period end</label>
                        <input id="period_end" v-model="form.period_end" type="date" required :max="maxPeriodEnd" class="wh-input" />
                    </div>
                </div>
                <p v-if="periodEndInFuture" class="text-sm text-amber-700">
                    Period end is after {{ maxPeriodEnd }}. Use today or the last completed weekday.
                </p>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing || periodEndInFuture" @click="submitCreate">
                        Create draft run
                    </button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
