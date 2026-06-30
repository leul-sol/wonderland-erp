<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormLabel from '../../../Components/FormLabel.vue';
import FormModal from '../../../Components/FormModal.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import { useQueryModal } from '../../../composables/useQueryModal';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    canCreate: { type: Boolean, default: false },
});

const items = computed(() => props.pageLoad?.items ?? []);
const categories = computed(() => props.pageLoad?.categories ?? []);

const { canManageInventoryItems, canReadPurchaseOrders, canCreatePurchaseOrders } = usePortalPermission();
const allowCreate = computed(() => props.canCreate && canManageInventoryItems());

const showCreateModal = ref(false);

const form = useForm({
    sku: '',
    name: '',
    unit: 'each',
    unit_cost: '',
    category_id: '',
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
    form.category_id = categories.value[0]?.id ?? '';
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
    when: () => allowCreate.value,
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
                <button v-if="allowCreate" type="button" class="wh-btn-primary" @click="openCreateModal">New item</button>
                <Link href="/inventory/alerts" class="wh-btn-secondary">Alerts</Link>
                <Link v-if="canReadPurchaseOrders()" href="/inventory/purchase-orders" class="wh-btn-secondary">Purchase orders</Link>
                <Link
                    v-if="canCreatePurchaseOrders()"
                    href="/inventory/purchase-orders?open=create"
                    class="wh-btn-secondary"
                >
                    Create PO
                </Link>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
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
        </PageDataSection>

        <FormModal
            v-if="allowCreate"
            :open="showCreateModal"
            title="New inventory item"
            subtitle="SKU master for stock, POs, and recipes"
            size="lg"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid items-end gap-4 sm:grid-cols-2">
                    <div>
                        <FormLabel for="sku" required>SKU</FormLabel>
                        <input id="sku" v-model="form.sku" type="text" required class="wh-input" placeholder="LINEN-SET" />
                    </div>
                    <div>
                        <FormLabel for="name" required>Name</FormLabel>
                        <input id="name" v-model="form.name" type="text" required class="wh-input" placeholder="Bed linen set" />
                    </div>
                    <div>
                        <FormLabel for="unit">Unit</FormLabel>
                        <input id="unit" v-model="form.unit" type="text" class="wh-input" placeholder="each, kg, box" />
                    </div>
                    <div>
                        <FormLabel for="unit_cost">Unit cost</FormLabel>
                        <MoneyField id="unit_cost" v-model="form.unit_cost" hide-label />
                    </div>
                    <div>
                        <FormLabel for="category_id">Category</FormLabel>
                        <select id="category_id" v-model="form.category_id" class="wh-input">
                            <option value="">None</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Group items for reporting (e.g. Linen, Food, Beverage).</p>
                    </div>
                    <div>
                        <FormLabel for="reorder_level">Reorder level</FormLabel>
                        <input id="reorder_level" v-model="form.reorder_level" type="number" min="0" step="0.001" class="wh-input" placeholder="20" />
                        <p class="mt-1 text-xs text-slate-500">Minimum stock before a low-stock alert fires.</p>
                    </div>
                    <div>
                        <FormLabel for="rotation_strategy">Rotation</FormLabel>
                        <select id="rotation_strategy" v-model="form.rotation_strategy" class="wh-input">
                            <option value="fifo">FIFO — first in, first out</option>
                            <option value="fefo">FEFO — first expiring, first out</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 pb-1">
                        <input id="is_perishable" v-model="form.is_perishable" type="checkbox" class="rounded border-slate-300" />
                        <label for="is_perishable" class="text-sm text-slate-700">Perishable (track expiry dates)</label>
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
