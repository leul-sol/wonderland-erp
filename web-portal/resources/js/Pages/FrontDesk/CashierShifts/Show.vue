<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    shift: { type: Object, required: true },
    report: { type: Object, default: () => ({}) },
});

const closeForm = useForm({
    closing_cash_counted: '',
});

const isOpen = computed(() => props.shift.status === 'open');
const expectedCash = computed(() => props.report.expected_cash ?? props.shift.expected_cash ?? null);

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = Number.parseFloat(value);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function formatWhen(value) {
    return value ? new Date(value).toLocaleString() : '—';
}

function useExpectedCount() {
    closeForm.closing_cash_counted = expectedCash.value ?? '';
}

async function closeShift() {
    const counted = Number.parseFloat(closeForm.closing_cash_counted ?? 0);
    const expected = Number.parseFloat(expectedCash.value ?? 0);
    const variance = counted - expected;

    const ok = await confirmAction({
        title: 'Close cashier shift',
        message:
            variance === 0
                ? `Close shift #${props.shift.id} with counted cash ETB ${formatMoney(counted)}?`
                : `Close shift #${props.shift.id}? Counted ETB ${formatMoney(counted)} vs expected ETB ${formatMoney(expected)} (variance ${formatMoney(variance)}).`,
        confirmLabel: 'Close shift',
    });

    if (!ok) {
        return;
    }

    closeForm.post(`/front-desk/cashier-shifts/${props.shift.id}/close`);
}
</script>

<template>
    <AppLayout :title="`Shift #${shift.id}`">
        <PageHeader :title="`Cashier shift #${shift.id}`" subtitle="Reconcile counted cash against expected collections">
            <template #actions>
                <StatusBadge :status="shift.status" />
                <Link href="/front-desk/cashier-shifts" class="wh-btn-secondary text-xs">All shifts</Link>
            </template>
        </PageHeader>

        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Opened</p>
                <p class="mt-1 text-sm text-slate-900">{{ formatWhen(shift.opened_at) }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Opening float</p>
                <p class="wh-money mt-1 text-lg font-semibold">ETB {{ formatMoney(shift.opening_cash_float) }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Expected cash</p>
                <p class="wh-money mt-1 text-lg font-semibold text-teal-800">ETB {{ formatMoney(expectedCash) }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Variance</p>
                <p class="wh-money mt-1 text-lg font-semibold" :class="Number(shift.variance) !== 0 ? 'text-amber-800' : 'text-slate-900'">
                    ETB {{ formatMoney(shift.variance) }}
                </p>
            </div>
        </div>

        <section v-if="isOpen" class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Close shift</h3>
            <p class="mb-4 text-sm text-slate-600">
                Count cash in drawer. Expected includes opening float plus folio and F&B cash payments posted during this shift.
            </p>
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="closeShift">
                <div class="min-w-[200px]">
                    <div class="mb-1 flex items-center justify-between">
                        <label for="closing_cash_counted" class="text-xs font-medium text-slate-600">Counted cash</label>
                        <button type="button" class="text-xs text-teal-700 hover:underline" @click="useExpectedCount">
                            Use expected
                        </button>
                    </div>
                    <MoneyField id="closing_cash_counted" v-model="closeForm.closing_cash_counted" required />
                </div>
                <button type="submit" class="wh-btn-primary" :disabled="closeForm.processing">Close shift</button>
            </form>
        </section>

        <section v-else class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Shift report</h3>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Closed</dt>
                    <dd>{{ formatWhen(shift.closed_at) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Counted cash</dt>
                    <dd class="wh-money">ETB {{ formatMoney(shift.closing_cash_counted) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Expected cash</dt>
                    <dd class="wh-money">ETB {{ formatMoney(shift.expected_cash ?? expectedCash) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Variance</dt>
                    <dd class="wh-money">ETB {{ formatMoney(shift.variance) }}</dd>
                </div>
            </dl>
        </section>
    </AppLayout>
</template>
