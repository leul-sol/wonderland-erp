<script setup>
import { computed } from 'vue';

const props = defineProps({
    steps: {
        type: Array,
        required: true,
    },
    currentKey: {
        type: String,
        default: '',
    },
});

const enriched = computed(() => {
    const keys = props.steps.map((s) => s.key);
    const currentIndex = keys.indexOf(props.currentKey);
    const allComplete = props.currentKey === 'locked';

    return props.steps.map((step, index) => {
        let state = 'upcoming';

        if (allComplete || (currentIndex >= 0 && index < currentIndex)) {
            state = 'complete';
        } else if (currentIndex >= 0 && index === currentIndex) {
            state = 'current';
        }

        return { ...step, state };
    });
});
</script>

<template>
    <ol class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-0">
        <li
            v-for="(step, index) in enriched"
            :key="step.key"
            class="flex flex-1 items-start gap-3 sm:flex-col sm:items-stretch sm:px-2"
        >
            <div class="flex items-center gap-3 sm:flex-col sm:items-center sm:text-center">
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                    :class="{
                        'bg-teal-700 text-white': step.state === 'complete',
                        'bg-teal-100 text-teal-900 ring-2 ring-teal-700': step.state === 'current',
                        'bg-slate-100 text-slate-500': step.state === 'upcoming',
                    }"
                >
                    {{ index + 1 }}
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">{{ step.label }}</p>
                    <p v-if="step.hint" class="text-xs text-slate-500">{{ step.hint }}</p>
                </div>
            </div>
            <div
                v-if="index < enriched.length - 1"
                class="hidden h-0.5 flex-1 self-center bg-slate-200 sm:block"
                :class="{ 'bg-teal-600': step.state === 'complete' }"
            />
        </li>
    </ol>
</template>
