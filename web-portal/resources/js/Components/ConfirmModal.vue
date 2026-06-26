<script setup>
import { useConfirm } from '../composables/useConfirm';

const { state, accept, cancel } = useConfirm();

function onBackdropClick(event) {
    if (event.target === event.currentTarget) {
        cancel();
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="state.open"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="state.title"
            @click="onBackdropClick"
        >
            <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-slate-900">{{ state.title }}</h2>
                <p v-if="state.message" class="mt-2 text-sm text-slate-600">{{ state.message }}</p>

                <div v-if="state.prompt" class="mt-4">
                    <label class="mb-1 block text-xs font-medium text-slate-600">{{ state.promptLabel }}</label>
                    <input
                        v-model="state.promptValue"
                        type="text"
                        class="wh-input"
                        :placeholder="state.promptPlaceholder"
                        @keyup.enter="accept"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="cancel">
                        {{ state.cancelLabel }}
                    </button>
                    <button
                        type="button"
                        class="wh-btn-primary"
                        :class="{ 'bg-red-700 hover:bg-red-800': state.variant === 'danger' }"
                        @click="accept"
                    >
                        {{ state.confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
