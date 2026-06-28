<script setup>
import { Link } from '@inertiajs/vue3';
import { MoreVertical } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const open = ref(false);
const triggerRef = ref(null);
const menuStyle = ref({});

const itemClass =
    'flex w-full items-center px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50';

function updatePosition() {
    if (!triggerRef.value) {
        return;
    }

    const rect = triggerRef.value.getBoundingClientRect();

    menuStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 4}px`,
        left: `${rect.right}px`,
        transform: 'translateX(-100%)',
        zIndex: 110,
    };
}

function toggleMenu() {
    open.value = !open.value;

    if (open.value) {
        nextTick(updatePosition);
    }
}

function closeMenu() {
    open.value = false;
}

function run(item) {
    closeMenu();
    item.onClick?.();
}

function onDocumentClick(event) {
    if (!open.value) {
        return;
    }

    if (triggerRef.value?.contains(event.target)) {
        return;
    }

    closeMenu();
}

function onViewportChange() {
    if (open.value) {
        updatePosition();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});
</script>

<template>
    <div ref="triggerRef" class="relative inline-flex">
        <button
            type="button"
            class="wh-action-menu-btn"
            aria-label="Actions"
            :aria-expanded="open"
            @click.stop="toggleMenu"
        >
            <MoreVertical class="h-4 w-4" />
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                class="min-w-[10rem] overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                :style="menuStyle"
            >
                <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
                    <Link
                        v-if="item.href"
                        :href="item.href"
                        :class="itemClass"
                        @click="closeMenu"
                    >
                        {{ item.label }}
                    </Link>
                    <button
                        v-else
                        type="button"
                        :class="itemClass"
                        @click="run(item)"
                    >
                        {{ item.label }}
                    </button>
                </template>
            </div>
        </Teleport>
    </div>
</template>
