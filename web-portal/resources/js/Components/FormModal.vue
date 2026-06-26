<script setup>
import { X } from 'lucide-vue-next';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    size: { type: String, default: 'lg' },
});

const emit = defineEmits(['close']);

const sizeClass = {
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
};

function onBackdropClick(event) {
    if (event.target === event.currentTarget) {
        emit('close');
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="title"
            @click="onBackdropClick"
        >
            <div
                class="flex max-h-[90vh] w-full flex-col rounded-xl border border-slate-200 bg-white shadow-xl"
                :class="sizeClass[size] ?? sizeClass.lg"
            >
                <div class="flex items-start justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ title }}</h2>
                        <p v-if="subtitle" class="mt-1 text-sm text-slate-600">{{ subtitle }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-slate-500 hover:bg-slate-100"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="overflow-y-auto px-6 py-4">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="border-t border-slate-100 px-6 py-4">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
