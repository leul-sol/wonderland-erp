<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    order: { type: Object, required: true },
    menuItems: { type: Array, default: () => [] },
    folio: { type: Object, default: null },
});

const lineForm = useForm({
    menu_item_id: '',
    quantity: 1,
});

const finalizeForm = useForm({});

const isOpen = computed(() => props.order.status === 'open');
const canFinalize = computed(() => isOpen.value && (props.order.lines?.length ?? 0) > 0);

const menuColumns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Item' },
    { key: 'price', label: 'Price', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const lineColumns = [
    { key: 'menu_item_name', label: 'Item' },
    { key: 'quantity', label: 'Qty' },
    { key: 'unit_price', label: 'Unit', class: 'text-right' },
    { key: 'line_total', label: 'Total', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function addLine(menuItemId) {
    lineForm.menu_item_id = menuItemId;
    lineForm.post(`/fb/orders/${props.order.id}/lines`, {
        preserveScroll: true,
        onSuccess: () => {
            lineForm.quantity = 1;
        },
    });
}

function finalize() {
    finalizeForm.post(`/fb/orders/${props.order.id}/finalize`);
}
</script>

<template>
    <AppLayout :title="`Order ${order.order_number}`">
        <PageHeader
            :title="`F&B order ${order.order_number}`"
            :subtitle="folio ? `Folio #${folio.id} · balance ETB ${formatMoney(folio.balance)}` : 'Guest folio order'"
        >
            <template #actions>
                <StatusBadge :status="order.status === 'open' ? 'draft' : order.status" />
                <Link
                    v-if="folio"
                    :href="`/front-desk/folios/${folio.id}`"
                    class="wh-btn-secondary text-xs"
                >
                    View folio
                </Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Menu items</h3>
                <DataTable :columns="menuColumns" :rows="menuItems" empty-message="No menu items available.">
                    <template #cell-price="{ row }">
                        <span class="wh-money">ETB {{ formatMoney(row.price) }}</span>
                    </template>
                    <template #cell-actions="{ row }">
                        <button
                            type="button"
                            class="wh-btn-primary text-xs"
                            :disabled="!isOpen || lineForm.processing"
                            @click="addLine(row.id)"
                        >
                            Add ×{{ lineForm.quantity }}
                        </button>
                    </template>
                </DataTable>
                <div v-if="isOpen" class="mt-4 flex items-end gap-3 border-t border-slate-200 pt-4">
                    <div>
                        <label for="quantity" class="mb-1 block text-xs font-medium text-slate-600">Quantity per add</label>
                        <input id="quantity" v-model.number="lineForm.quantity" type="number" min="1" max="20" class="wh-input w-24" />
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Order lines</h3>
                    <DataTable :columns="lineColumns" :rows="order.lines ?? []" empty-message="Add items from the menu.">
                        <template #cell-unit_price="{ row }">
                            <span class="wh-money">{{ formatMoney(row.unit_price) }}</span>
                        </template>
                        <template #cell-line_total="{ row }">
                            <span class="wh-money font-medium">{{ formatMoney(row.line_total) }}</span>
                        </template>
                    </DataTable>
                </section>

                <section class="wh-card p-4">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Subtotal</dt>
                            <dd class="wh-money">ETB {{ formatMoney(order.subtotal) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Service charge</dt>
                            <dd class="wh-money">ETB {{ formatMoney(order.service_charge_amount) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">VAT</dt>
                            <dd class="wh-money">ETB {{ formatMoney(order.vat_amount) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                            <dt>Order total</dt>
                            <dd class="wh-money text-teal-800">ETB {{ formatMoney(order.total_amount) }}</dd>
                        </div>
                    </dl>
                    <p v-if="isOpen" class="mt-3 text-xs text-slate-500">
                        Finalize posts SC/VAT charges to the guest folio.
                    </p>
                    <button
                        v-if="isOpen"
                        type="button"
                        class="wh-btn-primary mt-4 w-full"
                        :disabled="!canFinalize || finalizeForm.processing"
                        @click="finalize"
                    >
                        Finalize and post to folio
                    </button>
                    <p v-else class="mt-3 text-sm text-emerald-800">Order finalized.</p>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
