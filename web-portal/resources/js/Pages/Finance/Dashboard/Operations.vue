<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    dashboard: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppLayout title="Operations dashboard">
        <PageHeader title="Operations dashboard" subtitle="Cross-module KPIs from finance, hospitality, and workforce">
            <template #actions>
                <Link href="/finance/dashboard/executive" class="wh-btn-secondary">Executive</Link>
            </template>
        </PageHeader>

        <section class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Revenue</p>
                <p class="wh-money mt-2 text-xl font-semibold">ETB {{ dashboard.finance?.revenue ?? '0.00' }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Net income</p>
                <p class="wh-money mt-2 text-xl font-semibold">ETB {{ dashboard.finance?.net_income ?? '0.00' }}</p>
            </div>
            <div class="wh-card p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Active employees</p>
                <p class="mt-2 text-xl font-semibold text-slate-900">{{ dashboard.workforce?.active_employees ?? 0 }}</p>
            </div>
        </section>

        <section v-if="dashboard.hospitality" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Hospitality</h3>
            <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                <div v-for="(value, key) in dashboard.hospitality" :key="key" class="flex justify-between gap-4">
                    <dt class="capitalize text-slate-500">{{ String(key).replaceAll('_', ' ') }}</dt>
                    <dd class="font-medium text-slate-900">{{ value }}</dd>
                </div>
            </dl>
        </section>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Revenue by source</h3>
            <DataTable
                :columns="[
                    { key: 'source', label: 'Source' },
                    { key: 'amount', label: 'Amount', class: 'text-right' },
                ]"
                :rows="dashboard.revenue_by_source ?? []"
                empty-message="No revenue breakdown available."
            />
        </section>
    </AppLayout>
</template>
