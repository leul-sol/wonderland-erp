<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    journalEntries: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    accounts: { type: Array, default: () => [] },
    defaultEntryDate: { type: String, default: '' },
});

const showCreateModal = ref(false);

const form = useForm({
    description: '',
    entry_date: props.defaultEntryDate,
    source_reference: '',
    lines: [
        { account_code: props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' },
        { account_code: props.accounts[1]?.code ?? props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' },
    ],
});

const columns = [
    { key: 'entry_number', label: 'Entry #' },
    { key: 'entry_date', label: 'Date' },
    { key: 'description', label: 'Description' },
    { key: 'total_debit', label: 'Debit', class: 'text-right' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function openCreateModal() {
    form.reset();
    form.entry_date = props.defaultEntryDate;
    form.lines = [
        { account_code: props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' },
        { account_code: props.accounts[1]?.code ?? props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' },
    ];
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function addLine() {
    form.lines.push({ account_code: props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' });
}

function removeLine(index) {
    if (form.lines.length > 2) {
        form.lines.splice(index, 1);
    }
}

function submitCreate() {
    form.post('/finance/journals', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, { onOpen: openCreateModal });
</script>

<template>
    <AppLayout title="Journal entries">
        <PageHeader title="Manual journals" subtitle="Draft → finance approve → GM (if large) → posted">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">New journal</button>
            </template>
        </PageHeader>

        <DataTable list-title="Journal list" selectable :columns="columns" :rows="journalEntries" empty-message="No manual journal entries yet.">
            <template #empty>
                <p>No manual journal entries yet.</p>
                <button v-if="canCreate" type="button" class="wh-btn-primary mt-3" @click="openCreateModal">Create your first journal</button>
            </template>
            <template #cell-total_debit="{ row }">
                <span class="wh-money">{{ row.total_debit }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/finance/journals/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>

        <FormModal :open="showCreateModal" title="New manual journal" subtitle="Saved as draft until finance approval" size="xl" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                        <input v-model="form.description" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Entry date</label>
                        <input v-model="form.entry_date" type="date" class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Reference</label>
                        <input v-model="form.source_reference" type="text" class="wh-input" />
                    </div>
                </div>
                <div>
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Lines</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(line, index) in form.lines"
                            :key="index"
                            class="grid gap-3 rounded-lg border border-slate-200 p-3 lg:grid-cols-4"
                        >
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Account</label>
                                <select v-model="line.account_code" required class="wh-input">
                                    <option v-for="account in accounts" :key="account.id" :value="account.code">
                                        {{ account.code }} — {{ account.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Debit</label>
                                <input v-model="line.debit" type="number" step="0.01" min="0" class="wh-input" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Credit</label>
                                <input v-model="line.credit" type="number" step="0.01" min="0" class="wh-input" />
                            </div>
                            <div class="flex items-end justify-between gap-2">
                                <input v-model="line.description" type="text" placeholder="Line note" class="wh-input" />
                                <button v-if="form.lines.length > 2" type="button" class="text-xs text-red-600" @click="removeLine(index)">Remove</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="wh-btn-secondary mt-3" @click="addLine">Add line</button>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Save draft</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
