<script setup>
defineProps({
    title: { type: String, default: 'Unable to load data' },
    message: { type: String, required: true },
    recommendation: { type: String, default: '' },
    code: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <p class="font-medium">{{ title }}</p>
        <p class="mt-1">{{ message }}</p>
        <p v-if="recommendation" class="mt-2">
            <span class="font-medium">What to do:</span>
            {{ recommendation }}
        </p>
    </div>
</template>
