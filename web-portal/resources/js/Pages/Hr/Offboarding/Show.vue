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
    offboardingRecord: { type: Object, required: true },
    outstandingAssets: { type: Array, default: () => [] },
    clearanceSteps: { type: Array, default: () => [] },
    canUpdate: { type: Boolean, default: false },
    canReadSeverance: { type: Boolean, default: false },
    canReturnAssets: { type: Boolean, default: false },
});

const notesForm = useForm({
    notes: props.offboardingRecord.notes ?? '',
});

const assetColumns = [
    { key: 'asset_type', label: 'Asset' },
    { key: 'serial_number', label: 'Serial' },
    { key: 'assigned_date', label: 'Assigned' },
    { key: 'actions', label: '', class: 'text-right' },
];

const breadcrumbs = computed(() => [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Offboarding', href: '/hr/offboarding' },
    { label: props.offboardingRecord.employee_name ?? 'Dead file' },
]);

const isCompleted = computed(() => props.offboardingRecord.clearance_status === 'completed');
const hasOutstandingAssets = computed(() => props.outstandingAssets.length > 0);

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

function saveNotes() {
    notesForm.patch(`/hr/offboarding/${props.offboardingRecord.id}`, { preserveScroll: true });
}

async function startClearance() {
    const confirmed = await confirmAction({
        title: 'Start clearance',
        message: 'Move this dead file to clearance in progress?',
        confirmLabel: 'Start clearance',
    });

    if (!confirmed) {
        return;
    }

    useForm({ clearance_status: 'in_progress' }).patch(`/hr/offboarding/${props.offboardingRecord.id}`, {
        preserveScroll: true,
    });
}

async function completeClearance() {
    if (hasOutstandingAssets.value) {
        return;
    }

    const confirmed = await confirmAction({
        title: 'Complete dead file',
        message: 'Complete clearance and archive this employee? This cannot be undone.',
        confirmLabel: 'Complete & archive',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    useForm({
        clearance_status: 'completed',
        notes: notesForm.notes,
    }).patch(`/hr/offboarding/${props.offboardingRecord.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Dead file — ${offboardingRecord.employee_name}`">
        <PageHeader
            :title="offboardingRecord.employee_name ?? 'Dead file'"
            :subtitle="`${offboardingRecord.employee?.employee_number ?? ''} · ${reasonLabel(offboardingRecord.reason)}`"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <StatusBadge :status="offboardingRecord.clearance_status" />
                <Link
                    v-if="offboardingRecord.employee_id"
                    :href="`/hr/employees/${offboardingRecord.employee_id}`"
                    class="wh-btn-outline text-xs"
                >
                    Employee profile
                </Link>
                <Link href="/hr/offboarding" class="wh-btn-secondary text-xs">All offboarding</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Dead file workflow</h3>
            <ApprovalStepper :steps="clearanceSteps" :current-key="offboardingRecord.clearance_status" />
            <div v-if="canUpdate && !isCompleted" class="mt-4 flex flex-wrap justify-end gap-2">
                <button
                    v-if="offboardingRecord.clearance_status === 'pending'"
                    type="button"
                    class="wh-btn-secondary"
                    @click="startClearance"
                >
                    Start clearance
                </button>
                <button
                    v-if="offboardingRecord.clearance_status === 'in_progress'"
                    type="button"
                    class="wh-btn-primary"
                    :class="{ 'opacity-60': hasOutstandingAssets }"
                    @click="completeClearance"
                >
                    Complete dead file
                </button>
            </div>
        </section>

        <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Initiated</p>
                <p class="mt-1 text-sm font-medium text-slate-900">{{ offboardingRecord.initiated_date }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Last working day</p>
                <p class="mt-1 text-sm font-medium text-slate-900">{{ offboardingRecord.last_working_day }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Employee status</p>
                <p class="mt-1">
                    <StatusBadge :status="offboardingRecord.employee?.status ?? 'active'" />
                </p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Severance estimate</p>
                <p class="wh-money mt-1 text-sm font-medium text-slate-900">
                    {{ offboardingRecord.severance_amount ? `ETB ${formatMoney(offboardingRecord.severance_amount)}` : 'Not calculated' }}
                </p>
                <Link
                    v-if="canReadSeverance"
                    href="/payroll/severance"
                    class="mt-2 inline-block text-xs text-teal-700 hover:underline"
                >
                    Open severance queue
                </Link>
            </div>
        </section>

        <section class="wh-card mb-6 p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Asset clearance</h3>
                <Link
                    v-if="canReturnAssets && offboardingRecord.employee_id"
                    :href="`/hr/employees/${offboardingRecord.employee_id}?tab=assets`"
                    class="wh-btn-secondary text-xs"
                >
                    Manage assets
                </Link>
            </div>
            <p v-if="hasOutstandingAssets" class="mb-3 text-sm text-amber-800">
                {{ outstandingAssets.length }} asset(s) still assigned — return before completing clearance.
            </p>
            <p v-else class="mb-3 text-sm text-emerald-800">No outstanding assets.</p>
            <DataTable
                v-if="outstandingAssets.length"
                :columns="assetColumns"
                :rows="outstandingAssets"
                empty-message="No outstanding assets."
            >
                <template #cell-asset_type="{ row }">
                    {{ row.asset_type?.name ?? '—' }}
                </template>
            </DataTable>
        </section>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">HR notes</h3>
            <form class="space-y-3" @submit.prevent="saveNotes">
                <textarea
                    v-model="notesForm.notes"
                    rows="4"
                    class="wh-input w-full"
                    :disabled="!canUpdate"
                    placeholder="Exit interview notes, handover details…"
                />
                <div v-if="canUpdate" class="flex justify-end">
                    <button type="submit" class="wh-btn-secondary" :disabled="notesForm.processing">Save notes</button>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
