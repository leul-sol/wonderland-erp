<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    variance: { type: Object, default: () => ({}) },
    fiscalPeriods: { type: Array, default: () => [] },
    budgetLines: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    canCreate: { type: Boolean, default: false },
});

const lineForm = useForm({
    fiscal_period_id: props.filters.fiscal_period_id ?? '',
    account_code: '',
    budget_amount: '',
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

function submitLine() {
    lineForm.post('/finance/budget/lines', { preserveScroll: true, onSuccess: () => lineForm.reset('account_code', 'budget_amount') });
}
</script>

<template>
    <AppLayout title="Budget variance">
        <PageHeader title="Budget variance" subtitle="Actual vs budget net income and budget line entry">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Reports</Link>
            </template>
        </PageHeader>

        <PageDataSection :keys="['variance', 'fiscalPeriods', 'budgetLines', 'accounts']">
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

        <section class="grid gap-4 sm:grid-cols-3 mb-6">
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

        <section v-if="canCreate" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add budget line</h3>
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="submitLine">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Period</label>
                    <select v-model="lineForm.fiscal_period_id" class="wh-input w-44" required>
                        <option value="" disabled>Select period</option>
                        <option v-for="period in fiscalPeriods" :key="period.id" :value="period.id">
                            {{ period.year }}-P{{ period.period_number }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Account</label>
                    <select v-model="lineForm.account_code" class="wh-input w-40" required>
                        <option value="" disabled>Select account</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.code">{{ account.code }} — {{ account.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Amount</label>
                    <input v-model="lineForm.budget_amount" type="number" step="0.01" min="0" class="wh-input w-32" required />
                </div>
                <button type="submit" class="wh-btn-primary text-sm" :disabled="lineForm.processing">Save line</button>
            </form>
        </section>

        <DataTable
            list-title="Budget lines"
            :columns="[
                { key: 'fiscal_period_id', label: 'Period ID' },
                { key: 'account_code', label: 'Account' },
                { key: 'budget_amount', label: 'Budget', class: 'text-right' },
            ]"
            :rows="budgetLines"
            empty-message="No budget lines for this period."
        />
        </PageDataSection>
    </AppLayout>
</template>
