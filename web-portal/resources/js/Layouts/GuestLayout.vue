<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Wonderland ERP',
    },
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900">
        <div class="mx-auto flex min-h-screen max-w-md items-center px-4 py-12">
            <div class="w-full rounded-2xl border border-white/10 bg-white/95 p-8 shadow-2xl backdrop-blur">
                <div class="mb-8 text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Wonderland Hotel</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ title }}</h1>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
