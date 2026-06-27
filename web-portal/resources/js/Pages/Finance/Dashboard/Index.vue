<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    tab: { type: String, default: 'executive' },
    filters: { type: Object, default: () => ({}) },
    dashboard: { type: Object, default: () => ({}) },
});

const tabs = [
    { key: 'executive', label: 'Executive', href: '/finance/dashboard/executive' },
    { key: 'operations', label: 'Operations', href: '/finance/dashboard/operations' },
    { key: 'hotel', label: 'Hotel', href: '/finance/dashboard/hotel' },
    { key: 'restaurant', label: 'Restaurant', href: '/finance/dashboard/restaurant' },
    { key: 'finance', label: 'Finance', href: '/finance/dashboard/finance' },
];

const kpiLabels = {
    revenue: 'Revenue',
    expenses: 'Expenses',
    net_income: 'Net income',
    cash_position: 'Cash position',
    ar_outstanding: 'AR outstanding',
    ap_outstanding: 'AP outstanding',
};

function switchTab(href) {
    router.get(href, props.filters, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Finance dashboards">
        <PageHeader title="Finance dashboards" subtitle="Management KPIs across the hotel">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Financial reports</Link>
                <Link href="/finance/bi-reports" class="wh-btn-secondary">Operational reports</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="item in tabs"
                    :key="item.key"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    :class="tab === item.key ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700'"
                    @click="switchTab(item.href)"
                >
                    {{ item.label }}
                </button>
            </div>
        </section>

        <PageDataSection keys="dashboard">
            <p v-if="dashboard.loadFailed" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                {{ dashboard.loadError ?? 'Could not load dashboard data. Check that finance and hospitality services are running.' }}
            </p>

            <template v-else>
                <p v-if="dashboard.from && dashboard.to" class="mb-4 text-sm text-slate-600">
                    {{ dashboard.from }} → {{ dashboard.to }}
                </p>

                <section v-if="tab === 'executive' || tab === 'finance'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(value, key) in dashboard.kpis ?? {}" :key="key" class="wh-card p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ kpiLabels[key] ?? key }}</p>
                        <p class="wh-money mt-2 text-xl font-semibold text-slate-900">ETB {{ value }}</p>
                    </div>
                </section>

                <section v-else-if="tab === 'operations'" class="space-y-6">
                    <div class="grid gap-4 sm:grid-cols-3">
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
                    </div>
                    <div v-if="dashboard.hospitality" class="wh-card p-4">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Hospitality</h3>
                        <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                            <div v-for="(value, key) in dashboard.hospitality" :key="key" class="flex justify-between gap-4">
                                <dt class="capitalize text-slate-500">{{ String(key).replaceAll('_', ' ') }}</dt>
                                <dd class="font-medium text-slate-900">{{ value }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="wh-card p-4">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Revenue by source</h3>
                        <DataTable
                            :columns="[
                                { key: 'source', label: 'Source' },
                                { key: 'amount', label: 'Amount', class: 'text-right' },
                            ]"
                            :rows="dashboard.revenue_by_source ?? []"
                            empty-message="No revenue breakdown available."
                        />
                    </div>
                </section>

                <section v-else-if="tab === 'hotel'" class="space-y-6">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="(value, key) in dashboard.rooms ?? {}" :key="key" class="wh-card p-4">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ String(key).replaceAll('_', ' ') }}</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">{{ value }}</p>
                        </div>
                    </div>
                    <div v-if="dashboard.reservations" class="wh-card p-4">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Reservations</h3>
                        <dl class="grid gap-3 sm:grid-cols-2">
                            <div v-for="(value, key) in dashboard.reservations" :key="key" class="flex justify-between gap-4 text-sm">
                                <dt class="capitalize text-slate-500">{{ String(key).replaceAll('_', ' ') }}</dt>
                                <dd class="font-medium text-slate-900">{{ value }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section v-else-if="tab === 'restaurant'" class="grid gap-4 sm:grid-cols-2">
                    <div class="wh-card p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Orders</p>
                        <p class="mt-2 text-xl font-semibold text-slate-900">{{ dashboard.order_count ?? 0 }}</p>
                    </div>
                    <div class="wh-card p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Total revenue</p>
                        <p class="wh-money mt-2 text-xl font-semibold">ETB {{ dashboard.total_revenue ?? '0.00' }}</p>
                    </div>
                </section>
            </template>
        </PageDataSection>
    </AppLayout>
</template>
