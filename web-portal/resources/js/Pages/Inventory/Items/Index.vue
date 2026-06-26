<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    items: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);

const form = useForm({
    sku: '',
    name: '',
    unit: 'each',
    unit_cost: '',
    category_id: props.categories[0]?.id ?? '',
    rotation_strategy: 'fifo',
    is_perishable: false,
    reorder_level: '',
});

const columns = [
    { key: 'sku', label: 'SKU' },
    { key: 'name', label: 'Item' },
    { key: 'unit', label: 'Unit' },
    { key: 'quantity_on_hand', label: 'On hand', class: 'text-right' },
    { key: 'reorder_level', label: 'Reorder', class: 'text-right' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
];

function openCreateModal() {
    form.reset();
    form.unit = 'each';
    form.rotation_strategy = 'fifo';
    form.category_id = props.categories[0]?.id ?? '';
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/inventory/items', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, {
    onOpen() {
        openCreateModal();
    },
});
</script>

<template>
    <AppLayout title="Inventory items">
        <PageHeader title="Inventory items" subtitle="Stock on hand and reorder levels">
            <template #actions>
                <Link href="/inventory/item-categories" class="wh-btn-secondary">Categories</Link>
                <button type="button" class="wh-btn-primary" @click="openCreateModal">New item</button>
                <Link href="/inventory/alerts" class="wh-btn-secondary">Alerts</Link>
                <Link href="/inventory/purchase-orders" class="wh-btn-secondary">Purchase orders</Link>
                <Link href="/inventory/purchase-orders?open=create" class="wh-btn-secondary">Create PO</Link>
            </template>
        </PageHeader>

        <DataTable list-title="Inventory item list" selectable :columns="columns" :rows="items" empty-message="No inventory items found.">
            <template #cell-sku="{ row }">
                <Link :href="`/inventory/items/${row.id}`" class="wh-table-link">{{ row.sku }}</Link>
            </template>
            <template #cell-name="{ row }">
                <Link :href="`/inventory/items/${row.id}`" class="wh-table-link">{{ row.name }}</Link>
            </template>
            <template #cell-quantity_on_hand="{ row }">
                <span class="font-mono tabular-nums">{{ row.quantity_on_hand }}</span>
            </template>
            <template #cell-reorder_level="{ row }">
                <span class="font-mono tabular-nums">{{ row.reorder_level }}</span>
            </template>
            <template #cell-unit_cost="{ row }">
                <span class="wh-money">{{ row.unit_cost }}</span>
            </template>
        </DataTable>

        <FormModal
            :open="showCreateModal"
            title="New inventory item"
            subtitle="SKU master for stock, POs, and recipes"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="sku" class="mb-1 block text-sm font-medium text-slate-700">SKU</label>
                        <input id="sku" v-model="form.sku" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input id="name" v-model="form.name" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label for="unit" class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
                        <input id="unit" v-model="form.unit" type="text" class="wh-input" />
                    </div>
                    <div>
                        <label for="unit_cost" class="mb-1 block text-sm font-medium text-slate-700">Unit cost (ETB)</label>
                        <MoneyField id="unit_cost" v-model="form.unit_cost" />
                    </div>
                    <div>
                        <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                        <select id="category_id" v-model="form.category_id" class="wh-input">
                            <option value="">None</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="reorder_level" class="mb-1 block text-sm font-medium text-slate-700">Reorder level</label>
                        <input id="reorder_level" v-model="form.reorder_level" type="number" min="0" step="0.001" class="wh-input" />
                    </div>
                    <div>
                        <label for="rotation_strategy" class="mb-1 block text-sm font-medium text-slate-700">Rotation</label>
                        <select id="rotation_strategy" v-model="form.rotation_strategy" class="wh-input">
                            <option value="fifo">FIFO</option>
                            <option value="fefo">FEFO</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <input id="is_perishable" v-model="form.is_perishable" type="checkbox" class="rounded border-slate-300" />
                        <label for="is_perishable" class="text-sm text-slate-700">Perishable (expiry tracking)</label>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Create item</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
