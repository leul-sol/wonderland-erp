<script setup>
defineProps({
    chart: { type: Object, default: null },
});
</script>

<template>
    <div v-if="chart" class="space-y-4 p-5">
        <div v-if="!chart.bars?.length" class="py-10 text-center text-sm text-slate-500">
            No revenue posted for this date range.
        </div>

        <template v-else>
            <div class="flex items-end justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total collected</p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">ETB {{ chart.total }}</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2.5 w-2.5 rounded-sm bg-indigo-500" />
                        Revenue
                    </span>
                </div>
            </div>

            <div class="space-y-4">
                <div v-for="bar in chart.bars" :key="bar.source" class="grid grid-cols-[120px_1fr_auto] items-center gap-3">
                    <span class="truncate text-sm font-medium text-slate-700">{{ bar.source }}</span>
                    <div class="h-8 rounded-lg bg-slate-100">
                        <div
                            class="flex h-full min-w-[8px] items-center rounded-lg bg-gradient-to-r from-indigo-500 to-indigo-400 px-2 text-[10px] font-semibold text-white"
                            :style="{ width: `${Math.max(bar.percent, 8)}%` }"
                        />
                    </div>
                    <span class="wh-money text-sm font-semibold text-slate-900">ETB {{ bar.amount_label }}</span>
                </div>
            </div>
        </template>
    </div>
</template>
