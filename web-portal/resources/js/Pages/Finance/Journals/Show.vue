<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import ApprovalStepper from '../../../Components/ApprovalStepper.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    journalEntry: { type: Object, required: true },
    approvalSteps: { type: Array, default: () => [] },
    approvalCurrentStep: { type: String, default: '' },
    gmThreshold: { type: Number, default: 50000 },
    canApproveFinance: { type: Boolean, default: false },
    canApproveGm: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const approveForm = useForm({});

const lineColumns = [
    { key: 'account_code', label: 'Code' },
    { key: 'account_name', label: 'Account' },
    { key: 'debit', label: 'Debit', class: 'text-right' },
    { key: 'credit', label: 'Credit', class: 'text-right' },
];

function approve() {
    approveForm.post(`/finance/journals/${props.journalEntry.id}/approve`, { preserveScroll: true });
}

function destroyEntry() {
    router.delete(`/finance/journals/${props.journalEntry.id}`);
}
</script>

<template>
    <AppLayout :title="journalEntry.entry_number">
        <PageHeader
            :title="journalEntry.entry_number"
            :subtitle="`${journalEntry.entry_date} · ${journalEntry.description}`"
        >
            <template #actions>
                <StatusBadge :status="journalEntry.status" />
                <Link href="/finance/journals" class="wh-btn-secondary text-xs">All journals</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Approval workflow</h3>
            <ApprovalStepper :steps="approvalSteps" :current-key="approvalCurrentStep" />
            <p v-if="Number.parseFloat(journalEntry.total_debit) >= gmThreshold" class="mt-3 text-sm text-amber-800">
                Entry total ≥ ETB {{ gmThreshold.toLocaleString() }} — GM approval required before posting.
            </p>
            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <button
                    v-if="canDelete"
                    type="button"
                    class="wh-btn-secondary"
                    @click="destroyEntry"
                >
                    Delete draft
                </button>
                <button
                    v-if="canApproveFinance"
                    type="button"
                    class="wh-btn-primary"
                    :disabled="approveForm.processing"
                    @click="approve"
                >
                    Finance approve
                </button>
                <button
                    v-if="canApproveGm"
                    type="button"
                    class="wh-btn-primary"
                    :disabled="approveForm.processing"
                    @click="approve"
                >
                    GM approve and post
                </button>
            </div>
        </section>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Journal lines</h3>
            <DataTable :columns="lineColumns" :rows="journalEntry.lines ?? []" />
            <p class="mt-4 text-right text-sm font-semibold text-slate-900">
                DR {{ journalEntry.total_debit }} · CR {{ journalEntry.total_credit }}
            </p>
        </section>
    </AppLayout>
</template>
