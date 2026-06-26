<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    suppliers: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);

const form = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    payment_terms: 'Net 30',
});

const columns = [
    { key: 'name', label: 'Supplier' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'phone', label: 'Phone' },
    { key: 'payment_terms', label: 'Terms' },
    { key: 'outstanding_balance', label: 'Outstanding', class: 'text-right' },
];

function openCreateModal() {
    form.reset();
    form.payment_terms = 'Net 30';
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/inventory/suppliers', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}
</script>

<template>
    <AppLayout title="Suppliers">
        <PageHeader title="Suppliers" subtitle="Vendor master for procurement">
            <template #actions>
                <button type="button" class="wh-btn-primary" @click="openCreateModal">New supplier</button>
            </template>
        </PageHeader>

        <DataTable list-title="Supplier list" selectable :columns="columns" :rows="suppliers" empty-message="No suppliers found.">
            <template #cell-name="{ row }">
                <Link :href="`/inventory/suppliers/${row.id}`" class="wh-table-link">{{ row.name }}</Link>
            </template>
            <template #cell-outstanding_balance="{ row }">
                <span class="wh-money">ETB {{ row.outstanding_balance ?? '0.00' }}</span>
            </template>
        </DataTable>

        <FormModal
            :open="showCreateModal"
            title="New supplier"
            subtitle="Vendor master for purchase orders and payables"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Supplier name</label>
                    <input id="name" v-model="form.name" type="text" required class="wh-input" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_name" class="mb-1 block text-sm font-medium text-slate-700">Contact</label>
                        <input id="contact_name" v-model="form.contact_name" type="text" class="wh-input" />
                    </div>
                    <div>
                        <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                        <input id="phone" v-model="form.phone" type="text" class="wh-input" />
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                        <input id="email" v-model="form.email" type="email" class="wh-input" />
                    </div>
                    <div>
                        <label for="payment_terms" class="mb-1 block text-sm font-medium text-slate-700">Payment terms</label>
                        <input id="payment_terms" v-model="form.payment_terms" type="text" class="wh-input" />
                    </div>
                </div>
                <div>
                    <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                    <textarea id="address" v-model="form.address" rows="2" class="wh-input" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Create supplier</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
