<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    items: { type: Array, default: () => [] },
    emptyMessage: { type: String, default: 'No pending approvals.' },
});
</script>

<template>
    <div class="divide-y divide-slate-100">
        <div v-if="items.length === 0" class="py-8 text-center text-sm text-slate-500">
            {{ emptyMessage }}
        </div>

        <div v-for="(item, index) in items" :key="`${item.type}-${index}`" class="flex items-center justify-between gap-3 py-3">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ item.type }}</p>
                <p class="truncate text-sm font-medium text-slate-900">{{ item.title }}</p>
                <p class="truncate text-xs text-slate-500">{{ item.meta }}</p>
            </div>
            <Link :href="item.href" class="wh-btn-outline shrink-0 px-3 py-1.5 text-xs">
                Review
            </Link>
        </div>
    </div>
</template>
