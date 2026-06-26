<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    order: { type: Object, required: true },
    menuItems: { type: Array, default: () => [] },
    folio: { type: Object, default: null },
    routingHint: { type: String, default: '' },
    canPayBill: { type: Boolean, default: false },
});

const lineForm = useForm({
    menu_item_id: '',
    quantity: 1,
});

const finalizeForm = useForm({});
const payForm = useForm({
    amount: '',
    payment_method: 'cash',
    order_id: props.order.id,
});

const isOpen = computed(() => props.order.status === 'open');
const canFinalize = computed(() => isOpen.value && (props.order.lines?.length ?? 0) > 0);
const bill = computed(() => props.order.bill ?? null);
const outstanding = computed(() => Number.parseFloat(bill.value?.outstanding_balance ?? 0));

const finalizeLabel = computed(() => {
    const type = props.order.customer_type;
    if (type === 'hotel_guest') {
        return 'Finalize and post to folio';
    }
    if (type === 'outside_cash') {
        return 'Finalize and mark paid';
    }

    return 'Finalize order';
});

const finalizeMessage = computed(() => {
    const total = formatMoney(props.order.total_amount);
    if (props.order.customer_type === 'hotel_guest') {
        return `Post ETB ${total} (incl. SC/VAT) to the guest folio?`;
    }
    if (props.order.customer_type === 'outside_cash') {
        return `Finalize this order for ETB ${total} and mark the bill as paid?`;
    }

    return `Finalize order ${props.order.order_number} for ETB ${total}?`;
});

const customerLabel = computed(() => ({
    hotel_guest: 'Hotel guest',
    outside_cash: 'Walk-in cash',
    outside_credit: 'Walk-in credit',
    event: 'Event',
    employee: 'Staff meal',
})[props.order.customer_type] ?? props.order.customer_type);

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
    { key: 'actions', label: '', class: 'text-right' },
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

async function finalize() {
    const ok = await confirmAction({
        title: 'Finalize order',
        message: finalizeMessage.value,
        confirmLabel: 'Finalize',
    });

    if (!ok) {
        return;
    }

    finalizeForm.post(`/fb/orders/${props.order.id}/finalize`);
}

async function payBill() {
    const amount = payForm.amount ? Number.parseFloat(payForm.amount) : outstanding.value;
    const message = payForm.amount && amount < outstanding.value
        ? `Record a partial payment of ETB ${formatMoney(amount)} on this bill?`
        : `Record payment of ETB ${formatMoney(amount)} for bill #${bill.value?.id}?`;

    const ok = await confirmAction({
        title: 'Record bill payment',
        message,
        confirmLabel: 'Record payment',
    });

    if (!ok) {
        return;
    }

    payForm.post(`/fb/bills/${bill.value.id}/payments`, { preserveScroll: true });
}

function payFullOutstanding() {
    payForm.amount = bill.value?.outstanding_balance ?? '';
}

async function cancelOrder() {
    const ok = await confirmAction({
        title: 'Cancel order',
        message: `Cancel order ${props.order.order_number}? This cannot be undone.`,
        confirmLabel: 'Cancel order',
    });

    if (!ok) {
        return;
    }

    router.put(`/fb/orders/${props.order.id}/cancel`);
}

async function removeLine(line) {
    const ok = await confirmAction({
        title: 'Remove line',
        message: `Remove ${line.menu_item_name ?? 'this item'} from the order?`,
        confirmLabel: 'Remove',
    });

    if (!ok) {
        return;
    }

    router.delete(`/fb/orders/${props.order.id}/lines/${line.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Order ${order.order_number}`">
        <PageHeader
            :title="`F&B order ${order.order_number}`"
            :subtitle="
                folio
                    ? `${customerLabel} · Folio #${folio.id} · balance ETB ${formatMoney(folio.balance)}`
                    : `${customerLabel}${order.dining_table ? ` · Table ${order.dining_table.table_number}` : ''}`
            "
        >
            <template #actions>
                <StatusBadge :status="order.status === 'open' ? 'draft' : order.status" />
                <Link href="/fb/orders" class="wh-btn-secondary text-xs">Order queue</Link>
                <button
                    v-if="isOpen"
                    type="button"
                    class="wh-btn-secondary text-xs text-red-800"
                    @click="cancelOrder"
                >
                    Cancel order
                </button>
                <Link
                    v-if="folio"
                    :href="`/front-desk/folios/${folio.id}`"
                    class="wh-btn-secondary text-xs"
                >
                    View folio
                </Link>
            </template>
        </PageHeader>

        <p v-if="routingHint" class="mb-4 text-sm text-slate-600">{{ routingHint }}</p>

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
                        <template #cell-actions="{ row }">
                            <button
                                v-if="isOpen"
                                type="button"
                                class="text-xs text-red-700 hover:underline"
                                @click="removeLine(row)"
                            >
                                Remove
                            </button>
                        </template>
                    </DataTable>
                </section>

                <section class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">
                        {{ bill ? 'Bill' : 'Order totals' }}
                    </h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Subtotal</dt>
                            <dd class="wh-money">
                                ETB {{ formatMoney(bill?.subtotal ?? order.subtotal) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Service charge</dt>
                            <dd class="wh-money">
                                ETB {{ formatMoney(bill?.service_charge_amount ?? order.service_charge_amount) }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">VAT</dt>
                            <dd class="wh-money">
                                ETB {{ formatMoney(bill?.vat_amount ?? order.vat_amount) }}
                            </dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-2 font-semibold">
                            <dt>Total</dt>
                            <dd class="wh-money text-teal-800">
                                ETB {{ formatMoney(bill?.total_amount ?? order.total_amount) }}
                            </dd>
                        </div>
                        <template v-if="bill">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Paid</dt>
                                <dd class="wh-money">ETB {{ formatMoney(bill.paid_amount) }}</dd>
                            </div>
                            <div class="flex justify-between font-medium">
                                <dt class="text-slate-700">Outstanding</dt>
                                <dd class="wh-money text-amber-800">ETB {{ formatMoney(bill.outstanding_balance) }}</dd>
                            </div>
                            <div class="flex justify-between pt-1">
                                <dt class="text-slate-500">Bill status</dt>
                                <dd><StatusBadge :status="bill.status" /></dd>
                            </div>
                        </template>
                    </dl>

                    <button
                        v-if="isOpen"
                        type="button"
                        class="wh-btn-primary mt-4 w-full"
                        :disabled="!canFinalize || finalizeForm.processing"
                        @click="finalize"
                    >
                        {{ finalizeLabel }}
                    </button>
                    <p v-else-if="!canPayBill" class="mt-3 text-sm text-emerald-800">Order finalized.</p>
                </section>

                <section v-if="canPayBill" class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Record payment</h3>
                    <form class="space-y-3" @submit.prevent="payBill">
                        <div>
                            <label for="payment_method" class="mb-1 block text-xs font-medium text-slate-600">Payment method</label>
                            <select id="payment_method" v-model="payForm.payment_method" required class="wh-input">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank transfer</option>
                                <option value="mobile_money">Mobile money</option>
                            </select>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label for="pay_amount" class="text-xs font-medium text-slate-600">Amount (optional)</label>
                                <button type="button" class="text-xs text-teal-700 hover:underline" @click="payFullOutstanding">
                                    Pay full balance
                                </button>
                            </div>
                            <MoneyField id="pay_amount" v-model="payForm.amount" placeholder="Full outstanding if blank" />
                        </div>
                        <button type="submit" class="wh-btn-primary w-full" :disabled="payForm.processing">
                            Record payment
                        </button>
                    </form>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
