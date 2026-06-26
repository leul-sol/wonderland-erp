<script setup>
import { X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    error: { type: String, default: null },
    errorDetail: { type: Object, default: null },
    success: { type: String, default: null },
});

const visible = ref(false);
let hideTimer;

const title = computed(() => props.errorDetail?.title ?? 'Something went wrong');
const recommendation = computed(() => props.errorDetail?.recommendation ?? '');

watch(
    () => [props.error, props.success],
    () => {
        if (!props.error && !props.success) {
            visible.value = false;
            return;
        }

        visible.value = true;
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            visible.value = false;
        }, props.error ? 12000 : 6000);
    },
    { immediate: true },
);

onMounted(() => {
    if (props.error || props.success) {
        visible.value = true;
    }
});

function dismiss() {
    visible.value = false;
    clearTimeout(hideTimer);
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible && (error || success)"
            class="fixed right-4 top-4 z-[90] w-full max-w-md"
            role="alert"
            aria-live="assertive"
        >
            <div
                v-if="error"
                class="rounded-xl border border-red-200 bg-white px-4 py-4 shadow-lg"
            >
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-red-900">{{ title }}</p>
                        <p class="mt-1 text-sm text-red-800">{{ error }}</p>
                        <p v-if="recommendation" class="mt-2 text-sm text-red-700">
                            <span class="font-medium">What to do:</span>
                            {{ recommendation }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-red-500 hover:bg-red-50"
                        aria-label="Dismiss"
                        @click="dismiss"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <div
                v-if="success"
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg"
            >
                <div class="flex items-start justify-between gap-3">
                    <p>{{ success }}</p>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-emerald-600 hover:bg-emerald-100"
                        aria-label="Dismiss"
                        @click="dismiss"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
