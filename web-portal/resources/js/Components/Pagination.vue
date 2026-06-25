<script setup>
import { computed } from 'vue';

const props = defineProps({
    meta: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['page']);

const pages = computed(() => {
    const current = props.meta.current_page ?? 1;
    const last = props.meta.last_page ?? 1;
    const items = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i += 1) {
            items.push(i);
        }
        return items;
    }

    items.push(1);

    if (current > 3) {
        items.push('…');
    }

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);

    for (let i = start; i <= end; i += 1) {
        items.push(i);
    }

    if (current < last - 2) {
        items.push('…');
    }

    items.push(last);
    return items;
});

function goTo(page) {
    if (page === '…' || page === props.meta.current_page) {
        return;
    }
    emit('page', page);
}
</script>

<template>
    <nav v-if="meta.last_page > 1" class="wh-pagination" aria-label="Pagination">
        <button
            type="button"
            class="wh-pagination-btn"
            :disabled="meta.current_page <= 1"
            @click="goTo(meta.current_page - 1)"
        >
            Pre
        </button>

        <button
            v-for="(page, index) in pages"
            :key="`${page}-${index}`"
            type="button"
            class="wh-pagination-btn"
            :class="page === meta.current_page ? 'wh-pagination-btn-active' : ''"
            :disabled="page === '…'"
            @click="goTo(page)"
        >
            {{ page }}
        </button>

        <button
            type="button"
            class="wh-pagination-btn"
            :disabled="meta.current_page >= meta.last_page"
            @click="goTo(meta.current_page + 1)"
        >
            Next
        </button>
    </nav>
</template>
