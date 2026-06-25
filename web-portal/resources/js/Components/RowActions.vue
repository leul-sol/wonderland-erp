<script setup>
import { Link } from '@inertiajs/vue3';
import { MoreVertical } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const open = ref(false);

function run(item) {
    open.value = false;
    item.onClick?.();
}
</script>

<template>
    <div class="relative inline-flex">
        <button
            type="button"
            class="wh-action-menu-btn"
            aria-label="Actions"
            @click.stop="open = !open"
        >
            <MoreVertical class="h-4 w-4" />
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-20 mt-1 min-w-[140px] rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
        >
            <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="block px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                    @click="open = false"
                >
                    {{ item.label }}
                </Link>
                <button
                    v-else
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                    @click="run(item)"
                >
                    {{ item.label }}
                </button>
            </template>
        </div>
    </div>
</template>
