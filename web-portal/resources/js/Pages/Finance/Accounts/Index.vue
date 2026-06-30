<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import FormLabel from '../../../Components/FormLabel.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingAccount = ref(null);

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

function openCreateModal() {
    createForm.reset();
    createForm.type = 'expense';
    createForm.normal_balance = 'debit';
    createForm.clearErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(account) {
    editingAccount.value = account;
    editForm.name = account.name;
    editForm.type = account.type;
    editForm.sub_type = account.sub_type ?? '';
    editForm.normal_balance = account.normal_balance;
    editForm.is_active = account.is_active !== false;
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingAccount.value = null;
}

function submitCreate() {
    createForm.post('/finance/accounts', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function submitEdit() {
    if (!editingAccount.value) {
        return;
    }

    editForm.put(`/finance/accounts/${editingAccount.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}
</script>

<template>
    <AppLayout title="Chart of accounts">
        <PageHeader title="Chart of accounts" subtitle="General ledger account codes used across the hotel">
            <template #actions>
                <Link href="/finance/journals" class="wh-btn-secondary">Journals</Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">New account</button>
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
                        @click="openEditModal(row)"
                    >
                        Edit
                    </button>
                </template>
            </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canCreate"
            :open="showCreateModal"
            title="New account"
            subtitle="Add a GL account code for journals and reporting"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <FormLabel for="account_code" required>Code</FormLabel>
                    <input id="account_code" v-model="createForm.code" class="wh-input w-full" required maxlength="20" placeholder="6100" />
                </div>
                <div>
                    <FormLabel for="account_name" required>Name</FormLabel>
                    <input id="account_name" v-model="createForm.name" class="wh-input w-full" required placeholder="Utilities expense" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <FormLabel for="account_type" required>Type</FormLabel>
                        <select id="account_type" v-model="createForm.type" class="wh-input w-full">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <FormLabel for="account_normal_balance" required>Normal balance</FormLabel>
                        <select id="account_normal_balance" v-model="createForm.normal_balance" class="wh-input w-full">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">Save</button>
                </div>
            </template>
        </FormModal>

        <FormModal
            v-if="canUpdate"
            :open="showEditModal"
            :title="`Edit ${editingAccount?.code ?? 'account'}`"
            subtitle="Update account name, type, or active status"
            @close="closeEditModal"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div>
                    <FormLabel for="edit_account_name" required>Name</FormLabel>
                    <input id="edit_account_name" v-model="editForm.name" class="wh-input w-full" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <FormLabel for="edit_account_type" required>Type</FormLabel>
                        <select id="edit_account_type" v-model="editForm.type" class="wh-input w-full">
                            <option value="asset">Asset</option>
                            <option value="liability">Liability</option>
                            <option value="equity">Equity</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <FormLabel for="edit_account_normal_balance" required>Normal balance</FormLabel>
                        <select id="edit_account_normal_balance" v-model="editForm.normal_balance" class="wh-input w-full">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-300" />
                    Active account
                </label>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <button type="button" class="wh-btn-secondary" @click="closeEditModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="editForm.processing" @click="submitEdit">Update</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
