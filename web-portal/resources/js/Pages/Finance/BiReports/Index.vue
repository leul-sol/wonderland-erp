<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    category: { type: String, default: null },
    catalog: { type: Object, default: () => ({}) },
});

const reports = computed(() => props.catalog.reports ?? []);

const categories = [
    { key: '', label: 'All' },
    { key: 'finance', label: 'Finance' },
    { key: 'hospitality', label: 'Hospitality' },
    { key: 'workforce', label: 'Workforce' },
    { key: 'executive', label: 'Executive' },
];

function filterCategory(key) {
    router.get('/finance/bi-reports', key ? { category: key } : {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Operational reports">
        <PageHeader title="Operational reports" subtitle="Cross-department reports for finance, hotel, restaurant, and HR">
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Financial statements</Link>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="cat in categories"
                    :key="cat.key || 'all'"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    :class="(category || '') === cat.key ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700'"
                    @click="filterCategory(cat.key)"
                >
                    {{ cat.label }}
                </button>
            </div>
        </section>

        <PageDataSection keys="catalog">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="item in reports"
                    :key="item.slug"
                    :href="`/finance/bi-reports/${item.slug}`"
                    class="wh-card block p-4 transition hover:border-teal-600"
                >
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ item.category }}</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ item.name }}</p>
                </Link>
            </div>
            <p v-if="reports.length === 0" class="text-sm text-slate-600">No reports in this category.</p>
        </PageDataSection>
    </AppLayout>
</template>
