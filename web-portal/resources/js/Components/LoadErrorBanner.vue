<script setup>
defineProps({
    message: { type: String, required: true },
    code: { type: String, default: '' },
});
</script>

<template>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <p class="font-medium">Unable to load data from Identity service</p>
        <p class="mt-1">{{ message }}</p>
        <p v-if="code" class="mt-1 font-mono text-xs text-red-600">{{ code }}</p>
    </div>
</template>
