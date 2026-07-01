<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, X, XCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    successDuration: { type: Number, default: 5000 },
    errorDuration: { type: Number, default: 8000 },
});

const page = usePage();

const visible = ref(false);
const progress = ref(100);
const message = ref('');
const errorDetail = ref(null);
const variant = ref(null);

let hideTimer;
let progressTimer;
let startedAt = 0;
let activeDuration = 0;

const title = computed(() => errorDetail.value?.title ?? 'Something went wrong');
const recommendation = computed(() => errorDetail.value?.recommendation ?? '');

function clearTimers() {
    clearTimeout(hideTimer);
    clearInterval(progressTimer);
}

function dismiss() {
    visible.value = false;
    progress.value = 100;
    clearTimers();
}

function startAutoHide(duration) {
    clearTimers();
    activeDuration = duration;
    startedAt = Date.now();
    progress.value = 100;
    visible.value = true;

    progressTimer = setInterval(() => {
        const elapsed = Date.now() - startedAt;
        progress.value = Math.max(0, 100 - (elapsed / activeDuration) * 100);

        if (elapsed >= activeDuration) {
            dismiss();
        }
    }, 50);

    hideTimer = setTimeout(() => {
        dismiss();
    }, duration);
}

function clearPageFlash() {
    const flash = page.props.flash;

    if (!flash) {
        return;
    }

    flash.success = null;
    flash.error = null;
    flash.error_detail = null;
}

function consumeFlash(flash) {
    if (!flash?.error && !flash?.success) {
        return;
    }

    if (flash.error) {
        message.value = flash.error;
        errorDetail.value = flash.error_detail ?? null;
        variant.value = 'error';
        startAutoHide(props.errorDuration);
        clearPageFlash();

        return;
    }

    message.value = flash.success;
    errorDetail.value = null;
    variant.value = 'success';
    startAutoHide(props.successDuration);
    clearPageFlash();
}

onMounted(() => {
    consumeFlash(page.props.flash);
});

const removeSuccessListener = router.on('success', (event) => {
    const visit = event.detail.visit;

    // Notification polling reloads shared props only; flash is stale in client state.
    if (visit.only?.length > 0) {
        return;
    }

    consumeFlash(event.detail.page.props.flash);
});

onBeforeUnmount(() => {
    clearTimers();
    removeSuccessListener();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-1 opacity-0"
        >
            <div
                v-if="visible && message"
                class="fixed right-4 top-4 z-[90] w-full max-w-md"
                role="alert"
                aria-live="assertive"
            >
                <div
                    class="overflow-hidden rounded-xl border bg-white shadow-lg"
                    :class="variant === 'error' ? 'border-red-200' : 'border-emerald-200'"
                >
                    <div class="flex items-start gap-3 px-4 py-4">
                        <component
                            :is="variant === 'error' ? XCircle : CheckCircle2"
                            class="mt-0.5 h-5 w-5 shrink-0"
                            :class="variant === 'error' ? 'text-red-600' : 'text-emerald-600'"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-sm font-semibold"
                                :class="variant === 'error' ? 'text-red-900' : 'text-emerald-900'"
                            >
                                {{ variant === 'error' ? title : 'Success' }}
                            </p>
                            <p
                                class="mt-1 text-sm"
                                :class="variant === 'error' ? 'text-red-800' : 'text-emerald-800'"
                            >
                                {{ message }}
                            </p>
                            <p v-if="recommendation" class="mt-2 text-sm text-red-700">
                                <span class="font-medium">What to do:</span>
                                {{ recommendation }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                            aria-label="Dismiss"
                            @click="dismiss"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="h-1 bg-slate-100">
                        <div
                            class="h-full transition-[width] duration-75 ease-linear"
                            :class="variant === 'error' ? 'bg-red-500' : 'bg-emerald-500'"
                            :style="{ width: `${progress}%` }"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
