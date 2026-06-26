<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    order: { type: Object, required: true },
    period: { type: Object, default: null },
    employee: { type: Object, default: null },
    menuItems: { type: Array, default: () => [] },
});

const lineForm = useForm({ menu_item_id: '', quantity: 1 });
const finalizeForm = useForm({});

const isOpen = computed(() => props.order.status === 'open');
const canFinalize = computed(() => isOpen.value && (props.order.lines?.length ?? 0) > 0);

const employeeLabel = computed(() => {
    if (props.employee?.full_name) {
        return props.employee.full_name;
    }

    if (props.period?.employee_id) {
        return `Employee #${props.period.employee_id}`;
    }

    return 'Staff member';
});

const menuColumns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Item' },
    { key: 'price', label: 'Staff price', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

const lineColumns = [
    { key: 'menu_item_name', label: 'Item' },
    { key: 'quantity', label: 'Qty' },
    { key: 'line_total', label: 'Total', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function itemPrice(item) {
    return item.employee_price ?? item.price;
}

function addLine(menuItemId) {
    lineForm.menu_item_id = menuItemId;
    lineForm.post(`/consumption/orders/${props.order.id}/lines`, { preserveScroll: true });
}

async function finalize() {
    const ok = await confirmAction({
        title: 'Finalize meal order',
        message: `Post ETB ${formatMoney(props.order.total_amount)} to ${employeeLabel.value}'s consumption period?`,
        confirmLabel: 'Finalize',
    });

    if (!ok) {
        return;
    }

    finalizeForm.post(`/consumption/orders/${props.order.id}/finalize`);
}
</script>

<template>
    <AppLayout :title="`Meal ${order.order_number}`">
        <PageHeader
            :title="`Staff meal ${order.order_number}`"
            :subtitle="
                period
                    ? `${employeeLabel} · Period #${period.id} · ${period.period_start} – ${period.period_end}`
                    : `${employeeLabel} · consumption order`
            "
        >
            <template #actions>
                <StatusBadge :status="order.status === 'open' ? 'draft' : order.status" />
                <Link href="/consumption/periods" class="wh-btn-secondary text-xs">Back to periods</Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Menu</h3>
                <DataTable :columns="menuColumns" :rows="menuItems" empty-message="No menu items available.">
                    <template #cell-price="{ row }">
                        <span class="wh-money">ETB {{ formatMoney(itemPrice(row)) }}</span>
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
                <div v-if="isOpen" class="mt-4 border-t border-slate-200 pt-4">
                    <label for="quantity" class="mb-1 block text-xs font-medium text-slate-600">Quantity per add</label>
                    <input id="quantity" v-model.number="lineForm.quantity" type="number" min="1" max="20" class="wh-input w-24" />
                </div>
            </section>

            <aside class="space-y-4">
                <section class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Order lines</h3>
                    <DataTable :columns="lineColumns" :rows="order.lines ?? []" empty-message="Add items from the menu.">
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
                            <dt>Total</dt>
                            <dd class="wh-money text-teal-800">ETB {{ formatMoney(order.total_amount) }}</dd>
                        </div>
                    </dl>
                    <button
                        v-if="isOpen"
                        type="button"
                        class="wh-btn-primary mt-4 w-full"
                        :disabled="!canFinalize || finalizeForm.processing"
                        @click="finalize"
                    >
                        Finalize meal order
                    </button>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
