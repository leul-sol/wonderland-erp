<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    slug: { type: String, required: true },
    report: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    canExport: { type: Boolean, default: true },
});

const title = computed(() => props.report.name ?? props.slug.replaceAll('_', ' '));

const tableLines = computed(() => {
    const report = props.report;
    if (Array.isArray(report.lines)) {
        return report.lines;
    }
    if (Array.isArray(report.rows)) {
        return report.rows;
    }
    if (Array.isArray(report.employees)) {
        return report.employees;
    }
    if (Array.isArray(report.items)) {
        return report.items;
    }
    if (Array.isArray(report.orders)) {
        return report.orders;
    }

    return [];
});

const columns = computed(() => {
    const first = tableLines.value[0];
    if (!first || typeof first !== 'object') {
        return [];
    }

    return Object.keys(first).map((key) => ({
        key,
        label: key.replaceAll('_', ' '),
        class: typeof first[key] === 'number' ? 'text-right' : undefined,
    }));
});

const scalarFields = computed(() => {
    const skip = new Set(['lines', 'rows', 'employees', 'items', 'orders', 'name', 'report', 'kpis', 'bucket_totals']);
    const fields = [];

    for (const [key, value] of Object.entries(props.report)) {
        if (skip.has(key) || value === null || value === undefined) {
            continue;
        }
        if (typeof value === 'object') {
            continue;
        }
        fields.push({ key, value });
    }

    return fields;
});

const bucketTotals = computed(() => props.report.bucket_totals ?? null);

const kpiCards = computed(() => props.report.kpis ?? props.report.finance ?? null);

function exportUrl(format) {
    const params = new URLSearchParams({ format });
    if (props.filters.fiscal_period_id) {
        params.set('fiscal_period_id', props.filters.fiscal_period_id);
    }
    if (props.filters.from) {
        params.set('from', props.filters.from);
    }
    if (props.filters.to) {
        params.set('to', props.filters.to);
    }

    return `/finance/bi-reports/${props.slug}/export?${params.toString()}`;
}
</script>

<template>
    <AppLayout :title="title">
        <PageHeader :title="title" subtitle="Run and export operational report">
            <template #actions>
                <Link href="/finance/bi-reports" class="wh-btn-secondary">All reports</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="report">
            <section class="wh-card mb-6 p-4">
                <p v-if="report.from && report.to" class="mb-3 text-sm text-slate-600">
                    Period: {{ report.from }} → {{ report.to }}
                </p>
                <div v-if="canExport" class="flex flex-wrap gap-2">
                    <a :href="exportUrl('csv')" class="wh-btn-secondary text-xs">Download CSV</a>
                    <a :href="exportUrl('pdf')" class="wh-btn-secondary text-xs">Download PDF</a>
                    <a :href="exportUrl('excel')" class="wh-btn-secondary text-xs">Download Excel</a>
                </div>
                <p v-if="slug === 'payroll_payslip'" class="mt-3 text-sm text-amber-800">
                    For individual payslip PDFs, open the employee record under HR → Employees → Payslips.
                </p>
                <p v-if="slug === 'hr_guarantor_letter'" class="mt-3 text-sm text-amber-800">
                    For guarantor letters, open the employee record under HR → Employees → Guarantors.
                </p>
            </section>

            <section v-if="kpiCards" class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(value, key) in kpiCards" :key="key" class="wh-card p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ String(key).replaceAll('_', ' ') }}</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ value }}</p>
                </div>
            </section>

            <section v-if="bucketTotals" class="wh-card mb-6 p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Aging buckets</h3>
                <dl class="grid gap-2 sm:grid-cols-2">
                    <div v-for="(value, key) in bucketTotals" :key="key" class="flex justify-between gap-4 text-sm">
                        <dt class="capitalize text-slate-500">{{ String(key).replaceAll('_', ' ') }}</dt>
                        <dd class="wh-money font-medium">{{ value }}</dd>
                    </div>
                </dl>
            </section>

            <section v-if="tableLines.length" class="wh-card p-4">
                <DataTable
                    :columns="columns"
                    :rows="tableLines"
                    :empty-message="'No data for this report.'"
                />
            </section>

            <section v-else-if="scalarFields.length" class="wh-card p-4">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div v-for="field in scalarFields" :key="field.key" class="flex justify-between gap-4 text-sm">
                        <dt class="capitalize text-slate-500">{{ field.key.replaceAll('_', ' ') }}</dt>
                        <dd class="font-medium text-slate-900">{{ field.value }}</dd>
                    </div>
                </dl>
            </section>

            <p v-else class="text-sm text-slate-600">No tabular data returned for this report.</p>
        </PageDataSection>
    </AppLayout>
</template>
