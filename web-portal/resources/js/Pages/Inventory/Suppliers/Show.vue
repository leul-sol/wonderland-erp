<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    supplier: { type: Object, required: true },
    canPay: { type: Boolean, default: false },
});

const { canManageSuppliers } = usePortalPermission();

const payForm = useForm({
    amount: props.supplier.outstanding_balance ?? '',
    payment_method: 'bank_transfer',
    reference_number: '',
});

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function payFullBalance() {
    payForm.amount = props.supplier.outstanding_balance ?? payForm.amount;
}

async function recordPayment() {
    const amount = Number.parseFloat(payForm.amount ?? 0);
    const outstanding = Number.parseFloat(props.supplier.outstanding_balance ?? 0);
    const isPartial = amount > 0 && amount < outstanding;

    const ok = await confirmAction({
        title: 'Record supplier payment',
        message: isPartial
            ? `Record a partial payment of ETB ${formatMoney(amount)} to ${props.supplier.name}?`
            : `Record payment of ETB ${formatMoney(amount)} to ${props.supplier.name}?`,
        confirmLabel: 'Record payment',
    });

    if (!ok) {
        return;
    }

    payForm.post(`/inventory/suppliers/${props.supplier.id}/payments`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="supplier.name">
        <PageHeader :title="supplier.name" :subtitle="supplier.payment_terms ?? 'Supplier account'">
            <template #actions>
                <StatusBadge :status="supplier.is_active === false ? 'inactive' : 'active'" />
                <Link
                    v-if="canManageSuppliers()"
                    :href="`/inventory/suppliers?open=edit&id=${supplier.id}`"
                    class="wh-btn-secondary text-xs"
                >
                    Edit
                </Link>
                <Link href="/inventory/suppliers" class="wh-btn-secondary text-xs">All suppliers</Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <section class="wh-card p-4">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Contact</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ supplier.contact_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ supplier.phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ supplier.email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Payment terms</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ supplier.payment_terms ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Address</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ supplier.address ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-4">
                <section class="wh-card p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Outstanding balance</p>
                    <p class="wh-money mt-1 text-2xl font-semibold text-amber-800">
                        ETB {{ formatMoney(supplier.outstanding_balance) }}
                    </p>
                    <p class="mt-2 text-xs text-slate-500">
                        Updated when goods are received against linked purchase orders.
                    </p>
                </section>

                <section v-if="canPay" class="wh-card p-4">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Record payment</h3>
                    <form class="space-y-3" @submit.prevent="recordPayment">
                        <div>
                            <label for="payment_method" class="mb-1 block text-xs font-medium text-slate-600">Payment method</label>
                            <select id="payment_method" v-model="payForm.payment_method" required class="wh-input">
                                <option value="bank_transfer">Bank transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="mobile_money">Mobile money</option>
                            </select>
                        </div>
                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label for="amount" class="text-xs font-medium text-slate-600">Amount</label>
                                <button type="button" class="text-xs text-teal-700 hover:underline" @click="payFullBalance">
                                    Pay full balance
                                </button>
                            </div>
                            <MoneyField id="amount" v-model="payForm.amount" required />
                        </div>
                        <div>
                            <label for="reference_number" class="mb-1 block text-xs font-medium text-slate-600">Reference (optional)</label>
                            <input id="reference_number" v-model="payForm.reference_number" type="text" class="wh-input" />
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
