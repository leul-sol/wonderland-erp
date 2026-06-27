<script setup>
import { Link, router } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    reportType: { type: String, required: true },
    report: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const reportTabs = [
    { key: 'trial_balance', label: 'Trial balance' },
    { key: 'income_statement', label: 'Income statement' },
    { key: 'balance_sheet', label: 'Balance sheet' },
    { key: 'cash_flow', label: 'Cash flow' },
    { key: 'departmental', label: 'Departmental' },
];

function switchReport(type) {
    router.get('/finance/reports', { type, ...props.filters }, { preserveScroll: true });
}

function exportUrl(format) {
    const params = new URLSearchParams({
        report: props.reportType,
        format,
        ...(props.filters.fiscal_period_id ? { fiscal_period_id: props.filters.fiscal_period_id } : {}),
        ...(props.filters.from ? { from: props.filters.from } : {}),
        ...(props.filters.to ? { to: props.filters.to } : {}),
    });

    return `/finance/reports/export?${params.toString()}`;
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : String(value ?? '0.00');
}
</script>

<template>
    <AppLayout title="Financial reports">
        <PageHeader title="Financial reports" subtitle="Trial balance, income statement, balance sheet, cash flow, and departmental">
            <template #actions>
                <Link href="/finance/journals" class="wh-btn-secondary">Journals</Link>
                <Link href="/finance/payables" class="wh-btn-secondary">Payables</Link>
                <Link href="/finance/dashboard/executive" class="wh-btn-secondary">Dashboards</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="report">
        <section class="wh-card mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in reportTabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    :class="reportType === tab.key ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700'"
                    @click="switchReport(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>
            <p v-if="report.from && report.to" class="mt-3 text-sm text-slate-600">
                Period: {{ report.from }} → {{ report.to }}
            </p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a :href="exportUrl('csv')" class="wh-btn-secondary text-xs">Export CSV</a>
                <a :href="exportUrl('pdf')" class="wh-btn-secondary text-xs">Export PDF</a>
                <a :href="exportUrl('excel')" class="wh-btn-secondary text-xs">Export Excel</a>
            </div>
        </section>

        <section v-if="reportType === 'trial_balance'" class="wh-card p-4">
            <DataTable
                :columns="[
                    { key: 'account_code', label: 'Code' },
                    { key: 'account_name', label: 'Account' },
                    { key: 'debit_balance', label: 'Debit', class: 'text-right' },
                    { key: 'credit_balance', label: 'Credit', class: 'text-right' },
                ]"
                :rows="report.lines ?? []"
                empty-message="No trial balance lines for this period."
            />
            <p v-if="report.totals" class="mt-4 text-right text-sm font-semibold text-slate-900">
                Totals: DR {{ formatMoney(report.totals.debit) }} · CR {{ formatMoney(report.totals.credit) }}
            </p>
        </section>

        <section v-else-if="reportType === 'income_statement'" class="space-y-6">
            <div class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Revenue</h3>
                <DataTable
                    :columns="[
                        { key: 'account_code', label: 'Code' },
                        { key: 'account_name', label: 'Account' },
                        { key: 'amount', label: 'Amount', class: 'text-right' },
                    ]"
                    :rows="report.revenue?.lines ?? []"
                    empty-message="No revenue lines."
                />
                <p class="mt-3 text-right wh-money font-semibold">Total {{ report.revenue?.total ?? '0.00' }}</p>
            </div>
            <div class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Cost of goods sold</h3>
                <DataTable
                    :columns="[
                        { key: 'account_code', label: 'Code' },
                        { key: 'account_name', label: 'Account' },
                        { key: 'amount', label: 'Amount', class: 'text-right' },
                    ]"
                    :rows="report.cogs?.lines ?? []"
                    empty-message="No COGS lines."
                />
                <p class="mt-3 text-right wh-money font-semibold">Total {{ report.cogs?.total ?? '0.00' }}</p>
            </div>
            <p class="text-right text-base font-semibold text-slate-800">Gross profit: ETB {{ report.gross_profit ?? '0.00' }}</p>
            <div class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Operating expenses</h3>
                <DataTable
                    :columns="[
                        { key: 'account_code', label: 'Code' },
                        { key: 'account_name', label: 'Account' },
                        { key: 'amount', label: 'Amount', class: 'text-right' },
                    ]"
                    :rows="report.operating_expenses?.lines ?? []"
                    empty-message="No operating expense lines."
                />
                <p class="mt-3 text-right wh-money font-semibold">Total {{ report.operating_expenses?.total ?? '0.00' }}</p>
            </div>
            <p class="text-right text-lg font-semibold text-teal-800">Net income: ETB {{ report.net_income ?? '0.00' }}</p>
        </section>

        <section v-else-if="reportType === 'cash_flow'" class="wh-card p-4">
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Net income</dt>
                    <dd class="wh-money font-semibold">{{ report.operating?.net_income ?? '0.00' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Net cash from operations</dt>
                    <dd class="wh-money font-semibold">{{ report.operating?.net_cash_from_operations ?? '0.00' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Net change in cash</dt>
                    <dd class="wh-money font-semibold">{{ report.net_change_in_cash ?? '0.00' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">Opening cash</dt>
                    <dd class="wh-money font-semibold">{{ report.opening_cash ?? '0.00' }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:col-span-2">
                    <dt class="text-slate-500">Closing cash</dt>
                    <dd class="wh-money font-semibold">{{ report.closing_cash ?? '0.00' }}</dd>
                </div>
            </dl>
        </section>

        <section v-else-if="reportType === 'departmental'" class="wh-card p-4">
            <DataTable
                :columns="[
                    { key: 'source_module', label: 'Source' },
                    { key: 'account_code', label: 'Code' },
                    { key: 'account_name', label: 'Account' },
                    { key: 'revenue', label: 'Revenue', class: 'text-right' },
                ]"
                :rows="report.lines ?? []"
                empty-message="No departmental revenue for this period."
            />
            <p class="mt-4 text-right text-sm font-semibold text-slate-900">
                Total revenue: ETB {{ report.total_revenue ?? '0.00' }}
            </p>
        </section>

        <section v-else-if="reportType === 'balance_sheet'" class="space-y-6">
            <div class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Assets</h3>
                <DataTable
                    :columns="[
                        { key: 'account_code', label: 'Code' },
                        { key: 'account_name', label: 'Account' },
                        { key: 'balance', label: 'Balance', class: 'text-right' },
                    ]"
                    :rows="report.assets?.lines ?? []"
                    empty-message="No asset lines."
                />
                <p class="mt-3 text-right wh-money font-semibold">Total {{ report.assets?.total ?? '0.00' }}</p>
            </div>
            <div class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Liabilities</h3>
                <DataTable
                    :columns="[
                        { key: 'account_code', label: 'Code' },
                        { key: 'account_name', label: 'Account' },
                        { key: 'balance', label: 'Balance', class: 'text-right' },
                    ]"
                    :rows="report.liabilities?.lines ?? []"
                    empty-message="No liability lines."
                />
                <p class="mt-3 text-right wh-money font-semibold">Total {{ report.liabilities?.total ?? '0.00' }}</p>
            </div>
        </section>
        </PageDataSection>
    </AppLayout>
</template>
