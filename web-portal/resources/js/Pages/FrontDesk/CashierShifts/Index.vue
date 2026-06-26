<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    shifts: { type: Array, default: () => [] },
    openShift: { type: Object, default: null },
});

const openForm = useForm({
    opening_cash_float: '',
});

const columns = [
    { key: 'id', label: 'Shift #' },
    { key: 'opened_at', label: 'Opened' },
    { key: 'closed_at', label: 'Closed' },
    { key: 'opening_cash_float', label: 'Float', class: 'text-right' },
    { key: 'variance', label: 'Variance', class: 'text-right' },
    { key: 'status', label: 'Status' },
];

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

function submitOpen() {
    openForm.post('/front-desk/cashier-shifts', {
        preserveScroll: true,
        onSuccess: () => openForm.reset('opening_cash_float'),
    });
}
</script>

<template>
    <AppLayout title="Cashier shifts">
        <PageHeader title="Cashier shifts" subtitle="Open float, track cash collections, close with counted cash">
            <template #actions>
                <Link
                    v-if="openShift"
                    :href="`/front-desk/cashier-shifts/${openShift.id}`"
                    class="wh-btn-primary"
                >
                    Current shift #{{ openShift.id }}
                </Link>
            </template>
        </PageHeader>

        <section v-if="!openShift" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Open shift</h3>
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="submitOpen">
                <div class="min-w-[200px]">
                    <label for="opening_cash_float" class="mb-1 block text-xs font-medium text-slate-600">
                        Opening cash float (optional)
                    </label>
                    <MoneyField id="opening_cash_float" v-model="openForm.opening_cash_float" />
                </div>
                <button type="submit" class="wh-btn-primary" :disabled="openForm.processing">Open shift</button>
            </form>
        </section>

        <DataTable list-title="Shift history" :columns="columns" :rows="shifts" empty-message="No cashier shifts yet.">
            <template #cell-id="{ row }">
                <Link :href="`/front-desk/cashier-shifts/${row.id}`" class="wh-table-link">#{{ row.id }}</Link>
            </template>
            <template #cell-opened_at="{ row }">
                {{ formatWhen(row.opened_at) }}
            </template>
            <template #cell-closed_at="{ row }">
                {{ formatWhen(row.closed_at) }}
            </template>
            <template #cell-opening_cash_float="{ row }">
                <span class="wh-money">{{ formatMoney(row.opening_cash_float) }}</span>
            </template>
            <template #cell-variance="{ row }">
                <span class="wh-money" :class="Number(row.variance) !== 0 ? 'text-amber-800' : ''">
                    {{ formatMoney(row.variance) }}
                </span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
        </DataTable>
    </AppLayout>
</template>
