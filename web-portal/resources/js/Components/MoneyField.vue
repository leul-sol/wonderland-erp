<script setup>
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
    label: { type: String, default: 'Amount (ETB)' },
    id: { type: String, default: 'amount' },
    required: { type: Boolean, default: false },
    min: { type: [String, Number], default: '0' },
    step: { type: String, default: '0.01' },
    error: { type: String, default: '' },
    inline: { type: Boolean, default: false },
    hideLabel: { type: Boolean, default: false },
});
</script>

<template>
    <div>
        <label
            v-if="!hideLabel && !inline"
            :for="id"
            class="mb-1 block text-sm font-medium text-slate-700"
        >
            {{ label }}
        </label>
        <label
            v-else-if="!hideLabel && inline"
            :for="id"
            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
        >
            {{ label }}
        </label>
        <div
            class="flex overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-100"
        >
            <span
                class="flex shrink-0 items-center border-r border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-500"
            >
                ETB
            </span>
            <input
                :id="id"
                v-model="model"
                type="number"
                :min="min"
                :step="step"
                :required="required"
                :aria-label="label"
                class="min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0 font-mono tabular-nums"
            />
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
