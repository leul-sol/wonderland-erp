<script setup>
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
    label: { type: String, default: 'Amount (ETB)' },
    id: { type: String, default: 'amount' },
    required: { type: Boolean, default: false },
    min: { type: [String, Number], default: '0' },
    step: { type: String, default: '0.01' },
    error: { type: String, default: '' },
});
</script>

<template>
    <div>
        <label :for="id" class="mb-1 block text-sm font-medium text-slate-700">{{ label }}</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-500">ETB</span>
            <input
                :id="id"
                v-model="model"
                type="number"
                :min="min"
                :step="step"
                :required="required"
                class="wh-input pl-11 font-mono tabular-nums"
            />
        </div>
        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
