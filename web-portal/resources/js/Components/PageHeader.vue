<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Download, Printer, RefreshCw } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    showRefresh: { type: Boolean, default: true },
    showPrint: { type: Boolean, default: true },
    showExport: { type: Boolean, default: false },
    exportHref: { type: String, default: '' },
});

function refreshPage() {
    router.reload({ preserveScroll: true });
}

function printPage() {
    window.print();
}
</script>

<template>
    <div class="mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h1 class="wh-page-title">{{ title }}</h1>

                <nav v-if="breadcrumbs.length" class="wh-breadcrumb mt-2" aria-label="Breadcrumb">
                    <template v-for="(crumb, index) in breadcrumbs" :key="`${crumb.label}-${index}`">
                        <span v-if="index > 0" class="wh-breadcrumb-sep">/</span>
                        <Link
                            v-if="crumb.href"
                            :href="crumb.href"
                            class="wh-breadcrumb-link"
                        >
                            {{ crumb.label }}
                        </Link>
                        <span v-else class="text-slate-700">{{ crumb.label }}</span>
                    </template>
                </nav>

                <p v-if="subtitle" class="mt-2 text-sm text-slate-500">{{ subtitle }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="showRefresh"
                    type="button"
                    class="wh-icon-btn"
                    aria-label="Refresh"
                    @click="refreshPage"
                >
                    <RefreshCw class="h-[18px] w-[18px]" />
                </button>

                <button
                    v-if="showPrint"
                    type="button"
                    class="wh-icon-btn"
                    aria-label="Print"
                    @click="printPage"
                >
                    <Printer class="h-[18px] w-[18px]" />
                </button>

                <a
                    v-if="showExport && exportHref"
                    :href="exportHref"
                    class="wh-btn-outline"
                >
                    <Download class="h-4 w-4" />
                    Export
                </a>
                <button v-else-if="showExport" type="button" class="wh-btn-outline" disabled title="Export not available">
                    <Download class="h-4 w-4" />
                    Export
                </button>

                <slot name="actions" />
            </div>
        </div>

        <div v-if="$slots.toolbar" class="mt-4">
            <slot name="toolbar" />
        </div>
    </div>
</template>
