<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SupplierFormFields from '../../../Components/Inventory/SupplierFormFields.vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import { useQueryModal } from '../../../composables/useQueryModal';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
});

const { canManageSuppliers, canReadSuppliers } = usePortalPermission();

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingSupplier = ref(null);
const pendingEditId = ref(null);

const createForm = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    payment_terms: 'Net 30',
});

const editForm = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    payment_terms: '',
    is_active: true,
});

const columns = [
    { key: 'name', label: 'Supplier' },
    { key: 'contact_name', label: 'Contact' },
    { key: 'phone', label: 'Phone' },
    { key: 'payment_terms', label: 'Terms' },
    { key: 'outstanding_balance', label: 'Outstanding', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right w-16' },
];

function openCreateModal() {
    createForm.reset();
    createForm.payment_terms = 'Net 30';
    createForm.clearErrors();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    createForm.post('/inventory/suppliers', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function openEditModal(supplier) {
    editingSupplier.value = supplier;
    editForm.name = supplier.name ?? '';
    editForm.contact_name = supplier.contact_name ?? '';
    editForm.phone = supplier.phone ?? '';
    editForm.email = supplier.email ?? '';
    editForm.address = supplier.address ?? '';
    editForm.payment_terms = supplier.payment_terms ?? '';
    editForm.is_active = supplier.is_active !== false;
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingSupplier.value = null;
}

function submitEdit() {
    if (!editingSupplier.value) {
        return;
    }

    editForm.put(`/inventory/suppliers/${editingSupplier.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

useQueryModal(showCreateModal, {
    when: () => canManageSuppliers(),
    onOpen: openCreateModal,
});

useQueryModal(showEditModal, {
    expected: 'edit',
    when: () => canManageSuppliers(),
    onOpen: (params) => {
        const id = Number.parseInt(params.get('id') ?? '', 10);

        if (!id) {
            return;
        }

        const supplier = props.suppliers.find((row) => row.id === id);

        if (supplier) {
            openEditModal(supplier);
        } else {
            pendingEditId.value = id;
        }
    },
});

watch(
    () => props.suppliers,
    (rows) => {
        if (!pendingEditId.value || rows.length === 0) {
            return;
        }

        const supplier = rows.find((row) => row.id === pendingEditId.value);

        if (supplier) {
            openEditModal(supplier);
            pendingEditId.value = null;
        }
    },
);
</script>

<template>
    <AppLayout title="Suppliers">
        <PageHeader title="Suppliers" subtitle="Vendor master for procurement">
            <template #actions>
                <button v-if="canManageSuppliers()" type="button" class="wh-btn-primary" @click="openCreateModal">New supplier</button>
            </template>
        </PageHeader>

        <PageDataSection keys="suppliers">
        <DataTable list-title="Supplier list" selectable :columns="columns" :rows="suppliers" empty-message="No suppliers found.">
            <template #cell-name="{ row }">
                <Link v-if="canReadSuppliers()" :href="`/inventory/suppliers/${row.id}`" class="wh-table-link">{{ row.name }}</Link>
                <span v-else>{{ row.name }}</span>
            </template>
            <template #cell-outstanding_balance="{ row }">
                <span class="wh-money">ETB {{ row.outstanding_balance ?? '0.00' }}</span>
            </template>
            <template #cell-actions="{ row }">
                <RowActions
                    :items="[
                        ...(canReadSuppliers() ? [{ label: 'View', href: `/inventory/suppliers/${row.id}` }] : []),
                        ...(canManageSuppliers() ? [{ label: 'Edit', onClick: () => openEditModal(row) }] : []),
                    ]"
                />
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canManageSuppliers()"
            :open="showCreateModal"
            title="New supplier"
            subtitle="Vendor master for purchase orders and payables"
            size="lg"
            @close="closeCreateModal"
        >
            <form @submit.prevent="submitCreate">
                <SupplierFormFields :form="createForm" />
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="createForm.processing" @click="submitCreate">Create supplier</button>
                </div>
            </template>
        </FormModal>

        <FormModal
            v-if="canManageSuppliers()"
            :open="showEditModal"
            :title="`Edit ${editingSupplier?.name ?? 'supplier'}`"
            subtitle="Update vendor contact and payment terms"
            size="lg"
            @close="closeEditModal"
        >
            <form @submit.prevent="submitEdit">
                <SupplierFormFields :form="editForm" show-active-toggle />
            </form>
            <template #footer>
                <div class="flex items-center justify-between gap-3">
                    <Link
                        v-if="editingSupplier && canReadSuppliers()"
                        :href="`/inventory/suppliers/${editingSupplier.id}`"
                        class="wh-btn-outline text-sm"
                        @click="closeEditModal"
                    >
                        View balance
                    </Link>
                    <div class="ml-auto flex gap-3">
                        <button type="button" class="wh-btn-secondary" @click="closeEditModal">Cancel</button>
                        <button type="button" class="wh-btn-primary" :disabled="editForm.processing" @click="submitEdit">Save changes</button>
                    </div>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
