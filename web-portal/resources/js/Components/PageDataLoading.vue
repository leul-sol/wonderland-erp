<script setup>
defineProps({
    label: { type: String, default: 'Loading data…' },
});
</script>

<template>
    <div
        class="flex min-h-[40vh] flex-col items-center justify-center gap-3 py-16 text-sm text-slate-500"
        role="status"
        aria-live="polite"
    >
        <span class="inline-flex h-8 w-8 animate-spin rounded-full border-2 border-teal-600 border-t-transparent" />
        <p>{{ label }}</p>
    </div>
</template>
