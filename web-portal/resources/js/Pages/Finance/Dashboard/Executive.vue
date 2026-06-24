<script setup>
import { Link } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    dashboard: { type: Object, default: () => ({}) },
});

const kpiLabels = {
    revenue: 'Revenue',
    expenses: 'Expenses',
    net_income: 'Net income',
    cash_position: 'Cash position',
    ar_outstanding: 'AR outstanding',
    ap_outstanding: 'AP outstanding',
};
</script>

<template>
    <AppLayout title="Executive dashboard">
        <PageHeader title="Executive dashboard" subtitle="Finance KPIs embedded from S4">
            <template #actions>
                <Link href="/finance/dashboard/operations" class="wh-btn-secondary">Operations</Link>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
            </template>
        </PageHeader>

        <p v-if="dashboard.from && dashboard.to" class="mb-4 text-sm text-slate-600">
            {{ dashboard.from }} → {{ dashboard.to }}
        </p>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="(value, key) in dashboard.kpis ?? {}" :key="key" class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ kpiLabels[key] ?? key }}</p>
                <p class="wh-money mt-2 text-xl font-semibold text-slate-900">ETB {{ value }}</p>
            </div>
        </div>
    </AppLayout>
</template>
