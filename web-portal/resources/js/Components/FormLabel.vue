<script setup>
defineProps({
    for: { type: String, default: undefined },
    required: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <label
        :for="for"
        class="mb-1 block font-medium text-slate-700"
        :class="compact ? 'text-xs text-slate-600' : 'text-sm'"
    >
        <slot />
        <span v-if="required" class="text-red-600" aria-hidden="true"> *</span>
    </label>
</template>
