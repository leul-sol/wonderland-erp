<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    folio: { type: Object, required: true },
    reservation: { type: Object, default: null },
});

const chargeForm = useForm({
    description: '',
    amount: '',
    charge_category: 'other',
});

const settleForm = useForm({
    amount: props.folio.balance ?? '0',
    payment_method: 'cash',
});

const checkoutForm = useForm({});

const balance = computed(() => Number.parseFloat(props.folio.balance ?? 0));
const canSettle = computed(() => props.folio.status === 'open' && balance.value > 0);
const canCheckout = computed(() => props.folio.status === 'settled' && props.reservation?.status === 'checked_in');
const isPartialPayment = computed(() => {
    const amount = Number.parseFloat(settleForm.amount ?? 0);
    return amount > 0 && amount < balance.value;
});

const taxSummary = computed(() => {
    const lines = (props.folio.lines ?? []).filter((line) => line.line_type === 'charge');

    return lines.reduce(
        (totals, line) => ({
            subtotal: totals.subtotal + Number.parseFloat(line.subtotal ?? 0),
            serviceCharge: totals.serviceCharge + Number.parseFloat(line.service_charge_amount ?? 0),
            vat: totals.vat + Number.parseFloat(line.vat_amount ?? 0),
            total: totals.total + Number.parseFloat(line.amount ?? 0),
        }),
        { subtotal: 0, serviceCharge: 0, vat: 0, total: 0 },
    );
});

const lineColumns = [
    { key: 'description', label: 'Description' },
    { key: 'line_type', label: 'Type' },
    { key: 'subtotal', label: 'Subtotal', class: 'text-right' },
    { key: 'service_charge_amount', label: 'SC', class: 'text-right' },
    { key: 'vat_amount', label: 'VAT', class: 'text-right' },
    { key: 'amount', label: 'Total', class: 'text-right' },
];

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = Number.parseFloat(value);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function postCharge() {
    chargeForm.post(`/front-desk/folios/${props.folio.id}/charges`, {
        preserveScroll: true,
        onSuccess: () => chargeForm.reset('description', 'amount'),
    });
}

async function settleFolio() {
    const amount = Number.parseFloat(settleForm.amount ?? 0);
    const message = isPartialPayment.value
        ? `Record a partial payment of ETB ${formatMoney(amount)}? Balance will remain until fully paid.`
        : `Record payment of ETB ${formatMoney(amount)} and settle this folio?`;

    const ok = await confirmAction({
        title: isPartialPayment.value ? 'Record partial payment' : 'Settle folio',
        message,
        confirmLabel: isPartialPayment.value ? 'Record payment' : 'Settle folio',
    });

    if (!ok) {
        return;
    }

    settleForm.post(`/front-desk/folios/${props.folio.id}/settle`, { preserveScroll: true });
}

async function checkOut() {
    const ok = await confirmAction({
        title: 'Check out guest',
        message: 'Release the room and complete check-out for this guest?',
        confirmLabel: 'Check out',
    });

    if (!ok) {
        return;
    }

    checkoutForm.post(`/front-desk/folios/${props.folio.id}/check-out`);
}

function payFullBalance() {
    settleForm.amount = props.folio.balance ?? settleForm.amount;
}
</script>

<template>
    <AppLayout :title="`Folio #${folio.id}`">
        <PageHeader
            :title="`Folio #${folio.id}`"
            :subtitle="reservation ? `${reservation.guest_name} · Room ${reservation.room?.room_number ?? '—'}` : 'Guest folio'"
        >
            <template #actions>
                <StatusBadge :status="folio.status" />
                <Link
                    v-if="reservation"
                    :href="`/front-desk/reservations/${reservation.id}`"
                    class="wh-btn-secondary text-xs"
                >
                    Reservation
                </Link>
                <a
                    v-if="folio.status === 'settled' || reservation?.status === 'checked_out'"
                    :href="`/front-desk/folios/${folio.id}/invoice`"
                    class="wh-btn-secondary text-xs"
                    target="_blank"
                    rel="noopener"
                >
                    Download invoice
                </a>
                <Link
                    v-if="folio.status === 'open'"
                    :href="`/fb/orders?open=create&folio_id=${folio.id}`"
                    class="wh-btn-secondary text-xs"
                >
                    Post F&B
                </Link>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <section class="wh-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(taxSummary.subtotal) }}</p>
            </section>
            <section class="wh-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Service charge</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(taxSummary.serviceCharge) }}</p>
            </section>
            <section class="wh-card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">VAT</p>
                <p class="wh-money mt-1 text-lg font-semibold text-slate-900">ETB {{ formatMoney(taxSummary.vat) }}</p>
            </section>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
            <div class="space-y-6">
                <section class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Charges and payments</h3>
                    <DataTable :columns="lineColumns" :rows="folio.lines ?? []" empty-message="No lines posted yet.">
                        <template #cell-subtotal="{ row }">
                            <span class="wh-money">{{ formatMoney(row.subtotal) }}</span>
                        </template>
                        <template #cell-service_charge_amount="{ row }">
                            <span class="wh-money">{{ formatMoney(row.service_charge_amount) }}</span>
                        </template>
                        <template #cell-vat_amount="{ row }">
                            <span class="wh-money">{{ formatMoney(row.vat_amount) }}</span>
                        </template>
                        <template #cell-amount="{ row }">
                            <span class="wh-money font-semibold">{{ formatMoney(row.amount) }}</span>
                        </template>
                    </DataTable>
                </section>

                <section v-if="folio.status === 'open'" class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Post incidental charge</h3>
                    <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="postCharge">
                        <div class="sm:col-span-2">
                            <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                            <input id="description" v-model="chargeForm.description" type="text" required class="wh-input" />
                        </div>
                        <MoneyField id="charge_amount" v-model="chargeForm.amount" label="Charge amount (subtotal)" required />
                        <div>
                            <label for="charge_category" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                            <select id="charge_category" v-model="chargeForm.charge_category" class="wh-input">
                                <option value="room">Room</option>
                                <option value="fb">F&B</option>
                                <option value="minibar">Minibar</option>
                                <option value="laundry">Laundry</option>
                                <option value="event">Event</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex justify-end">
                            <button type="submit" class="wh-btn-primary" :disabled="chargeForm.processing">Post charge</button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="wh-card p-4">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Total charges</dt>
                            <dd class="wh-money font-medium">ETB {{ formatMoney(folio.total_charges) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Total payments</dt>
                            <dd class="wh-money font-medium">ETB {{ formatMoney(folio.total_payments) }}</dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-3">
                            <dt class="font-semibold text-slate-900">Balance due</dt>
                            <dd class="wh-money text-lg font-bold text-teal-800">ETB {{ formatMoney(folio.balance) }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="canSettle" class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Record payment</h3>
                    <form class="space-y-4" @submit.prevent="settleFolio">
                        <MoneyField
                            id="settle_amount"
                            v-model="settleForm.amount"
                            label="Payment amount"
                            required
                        />
                        <button type="button" class="text-xs font-medium text-teal-700 hover:text-teal-900" @click="payFullBalance">
                            Pay full balance (ETB {{ formatMoney(folio.balance) }})
                        </button>
                        <div>
                            <label for="payment_method" class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                            <select id="payment_method" v-model="settleForm.payment_method" class="wh-input">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank transfer</option>
                                <option value="mobile_money">Mobile money</option>
                            </select>
                        </div>
                        <button type="submit" class="wh-btn-primary w-full" :disabled="settleForm.processing">
                            {{ isPartialPayment ? 'Record partial payment' : 'Settle folio' }}
                        </button>
                    </form>
                </section>

                <section v-else-if="folio.status === 'settled'" class="wh-card p-4">
                    <p class="text-sm text-emerald-800">Folio settled. Ready for check-out.</p>
                </section>

                <section class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Check-out</h3>
                    <p v-if="!canCheckout" class="mb-3 text-xs text-slate-500">
                        Settle the folio balance before releasing the room.
                    </p>
                    <button
                        type="button"
                        class="wh-btn-primary w-full"
                        :disabled="!canCheckout || checkoutForm.processing"
                        @click="checkOut"
                    >
                        Check out guest
                    </button>
                </section>

                <section v-if="reservation" class="wh-card p-4 text-sm text-slate-600">
                    <p><span class="font-medium text-slate-900">Confirmation:</span> {{ reservation.confirmation_code }}</p>
                    <p class="mt-1">
                        <span class="font-medium text-slate-900">Stay:</span>
                        {{ reservation.check_in_date }} → {{ reservation.check_out_date }}
                    </p>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
