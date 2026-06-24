<script setup>
import { Link, router } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    variance: { type: Object, default: () => ({}) },
    fiscalPeriods: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

function filterByPeriod(event) {
    const fiscalPeriodId = event.target.value;
    router.get('/finance/budget', fiscalPeriodId ? { fiscal_period_id: fiscalPeriodId } : {}, { preserveScroll: true });
}

function exportUrl(format) {
    const params = new URLSearchParams({
        report: 'budget_variance',
        format,
        ...(props.filters.fiscal_period_id ? { fiscal_period_id: props.filters.fiscal_period_id } : {}),
    });

    return `/finance/reports/export?${params.toString()}`;
}
</script>

<template>
    <AppLayout title="Budget variance">
        <PageHeader title="Budget variance" subtitle="Actual vs budget net income for the fiscal period">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <label class="mb-1 block text-xs font-medium text-slate-600">Fiscal period</label>
            <select
                class="wh-input max-w-xs"
                :value="filters.fiscal_period_id ?? ''"
                @change="filterByPeriod"
            >
                <option value="">Current period</option>
                <option v-for="period in fiscalPeriods" :key="period.id" :value="period.id">
                    {{ period.year }}-P{{ period.period_number }} ({{ period.status }})
                </option>
            </select>
            <div class="mt-4 flex flex-wrap gap-2">
                <a :href="exportUrl('csv')" class="wh-btn-secondary text-xs">Export CSV</a>
                <a :href="exportUrl('pdf')" class="wh-btn-secondary text-xs">Export PDF</a>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Actual net income</p>
                <p class="wh-money mt-2 text-2xl font-semibold text-slate-900">ETB {{ variance.actual_net_income ?? '0.00' }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Budget net income</p>
                <p class="wh-money mt-2 text-2xl font-semibold text-slate-900">ETB {{ variance.budget_net_income ?? '0.00' }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Variance</p>
                <p class="wh-money mt-2 text-2xl font-semibold text-teal-800">ETB {{ variance.variance ?? '0.00' }}</p>
            </div>
        </section>
    </AppLayout>
</template>
