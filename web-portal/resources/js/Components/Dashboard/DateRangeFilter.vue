<script setup>
import { router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    from: { type: String, required: true },
    to: { type: String, required: true },
    label: { type: String, default: '' },
});

const form = reactive({
    from: props.from,
    to: props.to,
});

const preset = ref('');

watch(
    () => [props.from, props.to],
    ([from, to]) => {
        form.from = from;
        form.to = to;
    },
);

function applyRange() {
    router.get('/', { from: form.from, to: form.to }, { preserveScroll: true });
}

function setPreset(value) {
    if (!value) {
        return;
    }

    const today = new Date();
    const end = today.toISOString().slice(0, 10);

    if (value === 'month') {
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        form.from = start.toISOString().slice(0, 10);
        form.to = end;
    } else if (value === '30d') {
        const start = new Date(today);
        start.setDate(start.getDate() - 29);
        form.from = start.toISOString().slice(0, 10);
        form.to = end;
    } else if (value === 'year') {
        const start = new Date(today.getFullYear(), 0, 1);
        form.from = start.toISOString().slice(0, 10);
        form.to = end;
    }

    applyRange();
    preset.value = '';
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-1.5">
        <input
            v-model="form.from"
            type="date"
            aria-label="From date"
            class="wh-dash-date-input"
        />
        <span class="text-[11px] text-slate-400">–</span>
        <input
            v-model="form.to"
            type="date"
            aria-label="To date"
            class="wh-dash-date-input"
        />
        <button type="button" class="wh-dash-date-btn" @click="applyRange">
            Apply
        </button>
        <select
            v-model="preset"
            class="wh-dash-date-select"
            aria-label="Quick date range"
            @change="setPreset(preset)"
        >
            <option value="" disabled>Quick range</option>
            <option value="month">This month</option>
            <option value="30d">Last 30 days</option>
            <option value="year">This year</option>
        </select>
    </div>
</template>
