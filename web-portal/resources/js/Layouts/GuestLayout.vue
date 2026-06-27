<script setup>
import AppLogo from '../Components/AppLogo.vue';
import FlashHost from '../Components/FlashHost.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'Wonderland ERP',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const page = usePage();
const brand = computed(() => page.props.brand ?? {});
</script>

<template>
    <div class="wh-auth-page relative flex min-h-screen flex-col">
        <FlashHost />

        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-20 top-0 h-72 w-72 rounded-full bg-teal-400/15 blur-3xl" />
            <div class="absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-teal-700/10 blur-3xl" />
            <div class="absolute left-1/2 top-1/3 h-56 w-56 -translate-x-1/2 rounded-full bg-emerald-300/10 blur-3xl" />
        </div>

        <header class="relative z-10 flex flex-col items-center px-4 pt-10 sm:pt-14">
            <div class="flex justify-center">
                <AppLogo size="xl" :show-name="false" />
            </div>
        </header>

        <main class="relative z-10 flex flex-1 items-start justify-center px-4 py-8 sm:py-10">
            <div class="w-full max-w-[420px]">
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-xl shadow-slate-200/60">
                    <div class="h-1 bg-gradient-to-r from-teal-700 via-teal-600 to-emerald-500" />

                    <div class="p-8 sm:p-9">
                        <div class="mb-7 text-center">
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                {{ title }}
                            </h1>
                            <p v-if="subtitle" class="mt-2 text-sm leading-relaxed text-slate-500">
                                {{ subtitle }}
                            </p>
                        </div>

                        <slot />
                    </div>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    {{ brand.tagline }} · Secure staff access only
                </p>
            </div>
        </main>
    </div>
</template>

<style scoped>
.wh-auth-page {
    background-color: #f8fafc;
    background-image: radial-gradient(circle at 1px 1px, rgb(148 163 184 / 0.14) 1px, transparent 0);
    background-size: 22px 22px;
}
</style>
