<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    moduleKey: {
        type: String,
        required: true,
    },
    moduleLabel: {
        type: String,
        required: true,
    },
    phase: {
        type: Number,
        required: true,
    },
});
</script>

<template>
    <AppLayout :title="moduleLabel">
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Phase {{ phase }}</p>
            <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ moduleLabel }}</h2>
            <p class="mx-auto mt-3 max-w-lg text-sm text-slate-600">
                This module is planned in Phase {{ phase }}. APIs are ready; UI screens will be added in the next
                sprint.
            </p>
            <Link
                href="/"
                class="mt-6 inline-flex rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800"
            >
                Back to dashboard
            </Link>
        </div>
    </AppLayout>
</template>
