<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    accounts: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
});

const showCreate = ref(false);
const editingId = ref(null);

const createForm = useForm({
    code: '',
    name: '',
    type: 'expense',
    sub_type: '',
    normal_balance: 'debit',
});

const editForm = useForm({
    name: '',
    type: 'expense',
    sub_type: '',
    normal_balance: 'debit',
    is_active: true,
});

const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Account name' },
    { key: 'type', label: 'Type' },
    { key: 'normal_balance', label: 'Normal balance' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function openEdit(account) {
    editingId.value = account.id;
    editForm.name = account.name;
    editForm.type = account.type;
    editForm.sub_type = account.sub_type ?? '';
    editForm.normal_balance = account.normal_balance;
    editForm.is_active = account.is_active !== false;
}

function closeEdit() {
    editingId.value = null;
}

function submitCreate() {
    createForm.post('/finance/accounts', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            showCreate.value = false;
        },
    });
}

function submitEdit() {
    editForm.put(`/finance/accounts/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
}
</script>

<template>
    <AppLayout title="Chart of accounts">
        <PageHeader title="Chart of accounts" subtitle="General ledger account codes used across the hotel">
            <template #actions>
                <Link href="/finance/journals" class="wh-btn-secondary">Journals</Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="showCreate = true">New account</button>
            </template>
        </PageHeader>

        <PageDataSection keys="accounts">
            <DataTable list-title="Account list" selectable :columns="columns" :rows="accounts" empty-message="No accounts found.">
                <template #cell-is_active="{ row }">
                    <StatusBadge :status="row.is_active === false ? 'inactive' : 'active'" />
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="wh-btn-secondary text-xs"
                        @click="openEdit(row)"
                    >
                        Edit
                    </button>
                </template>
            </DataTable>
        </PageDataSection>

        <FormModal v-if="showCreate" title="New account" @close="showCreate = false">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="code">Code</label>
                    <input id="code" v-model="createForm.code" class="wh-input w-full" required maxlength="20" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="name">Name</label>
                    <input id="name" v-model="createForm.name" class="wh-input w-full" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="type">Type</label>
                        <select id="type" v-model="createForm.type" class="wh-input w-full">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="normal_balance">Normal balance</label>
                        <select id="normal_balance" v-model="createForm.normal_balance" class="wh-input w-full">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="wh-btn-secondary" @click="showCreate = false">Cancel</button>
                    <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Save</button>
                </div>
            </form>
        </FormModal>

        <FormModal v-if="editingId" title="Edit account" @close="closeEdit">
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="edit-name">Name</label>
                    <input id="edit-name" v-model="editForm.name" class="wh-input w-full" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="edit-type">Type</label>
                        <select id="edit-type" v-model="editForm.type" class="wh-input w-full">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="edit-normal">Normal balance</label>
                        <select id="edit-normal" v-model="editForm.normal_balance" class="wh-input w-full">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-300" />
                    Active account
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="wh-btn-secondary" @click="closeEdit">Cancel</button>
                    <button type="submit" class="wh-btn-primary" :disabled="editForm.processing">Update</button>
                </div>
            </form>
        </FormModal>
    </AppLayout>
</template>
