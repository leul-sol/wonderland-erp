<script setup>
import { Link } from '@inertiajs/vue3';
import EmptyState from '../EmptyState.vue';

defineProps({
    items: { type: Array, default: () => [] },
    emptyTitle: { type: String, default: 'All caught up' },
    emptyMessage: { type: String, default: 'No pending approvals right now.' },
});
</script>

<template>
    <div class="divide-y divide-slate-100">
        <EmptyState
            v-if="items.length === 0"
            :title="emptyTitle"
            :description="emptyMessage"
            variant="inbox"
            compact
        />

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
