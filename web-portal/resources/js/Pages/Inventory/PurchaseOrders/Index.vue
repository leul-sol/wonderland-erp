<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    pageLoad: { type: Object, default: null },
});

const { canCreatePurchaseOrders, canReadInventoryItems } = usePortalPermission();

const purchaseOrders = computed(() => props.pageLoad?.purchaseOrders ?? []);
const inventoryItems = computed(() => props.pageLoad?.inventoryItems ?? []);
const suppliers = computed(() => props.pageLoad?.suppliers ?? []);

const showCreateModal = ref(false);

const form = useForm({
    vendor_name: '',
    lines: [
        {
            inventory_item_id: '',
            quantity: 1,
            unit_cost: '',
        },
    ],
});

const lineTotal = computed(() =>
    form.lines.reduce((sum, line) => {
        const qty = Number.parseFloat(line.quantity) || 0;
        const cost = Number.parseFloat(line.unit_cost) || 0;
        return sum + qty * cost;
    }, 0),
);

const columns = [
    { key: 'po_number', label: 'PO #' },
    { key: 'vendor_name', label: 'Vendor' },
    { key: 'status', label: 'Status' },
    { key: 'approval_tier', label: 'Tier' },
    { key: 'total_amount', label: 'Total', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function openCreateModal() {
    form.reset();
    form.vendor_name = suppliers.value[0]?.name ?? '';
    form.lines = [
        {
            inventory_item_id: inventoryItems.value[0]?.id ?? '',
            quantity: 1,
            unit_cost: inventoryItems.value[0]?.unit_cost ?? '',
        },
    ];
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function addLine() {
    const item = inventoryItems.value[0];
    form.lines.push({
        inventory_item_id: item?.id ?? '',
        quantity: 1,
        unit_cost: item?.unit_cost ?? '',
    });
}

function removeLine(index) {
    if (form.lines.length > 1) {
        form.lines.splice(index, 1);
    }
}

function applySupplier(name) {
    form.vendor_name = name;
}

function onItemChange(line) {
    const item = inventoryItems.value.find((i) => String(i.id) === String(line.inventory_item_id));
    if (item) {
        line.unit_cost = item.unit_cost;
    }
}

function submitCreate() {
    form.post('/inventory/purchase-orders', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, {
    when: () => canCreatePurchaseOrders(),
    onOpen: openCreateModal,
});
</script>

<template>
    <AppLayout title="Purchase orders">
        <PageHeader title="Purchase orders" subtitle="Create, approve, and receive goods">
            <template #actions>
                <Link v-if="canReadInventoryItems()" href="/inventory/items" class="wh-btn-secondary">Inventory</Link>
                <button v-if="canCreatePurchaseOrders()" type="button" class="wh-btn-primary" @click="openCreateModal">Create PO</button>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <DataTable list-title="Purchase order list" selectable :columns="columns" :rows="purchaseOrders" empty-message="No purchase orders found.">
            <template #empty>
                <EmptyState
                    title="No purchase orders yet"
                    description="Create a draft PO when stores need to order goods. It will route through department, finance, and GM approval before receiving."
                    variant="table"
                >
                    <template #action>
                        <button v-if="canCreatePurchaseOrders()" type="button" class="wh-btn-primary" @click="openCreateModal">
                            Create your first PO
                        </button>
                    </template>
                </EmptyState>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-total_amount="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_amount) }}</span>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/inventory/purchase-orders/${row.id}`" class="wh-btn-secondary text-xs">Open</Link>
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canCreatePurchaseOrders()"
            :open="showCreateModal"
            title="Create purchase order"
            subtitle="Draft PO — submit for tiered approval when ready"
            size="xl"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label for="vendor_name" class="mb-1 block text-sm font-medium text-slate-700">Vendor name</label>
                    <input id="vendor_name" v-model="form.vendor_name" type="text" required class="wh-input" />
                    <div v-if="suppliers.length" class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="supplier in suppliers.slice(0, 6)"
                            :key="supplier.id"
                            type="button"
                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 hover:bg-teal-50"
                            @click="applySupplier(supplier.name)"
                        >
                            {{ supplier.name }}
                        </button>
                    </div>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-4"
                    >
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-slate-600">Item</label>
                            <select v-model="line.inventory_item_id" required class="wh-input" @change="onItemChange(line)">
                                <option value="" disabled>Select item</option>
                                <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                                    {{ item.sku }} — {{ item.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Qty</label>
                            <input v-model="line.quantity" type="number" min="0.001" step="0.001" required class="wh-input" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Unit cost</label>
                            <input v-model="line.unit_cost" type="number" min="0" step="0.01" required class="wh-input" />
                        </div>
                        <div class="flex justify-end sm:col-span-4">
                            <button v-if="form.lines.length > 1" type="button" class="text-xs text-red-600 hover:underline" @click="removeLine(index)">
                                Remove line
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                    <button type="button" class="wh-btn-secondary" @click="addLine">Add line</button>
                    <p class="wh-money text-sm font-semibold">Estimated total ETB {{ lineTotal.toFixed(2) }}</p>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Save draft PO</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
