<script setup>
import SidebarNav from '../Components/SidebarNav.vue';
import TopBar from '../Components/TopBar.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const flashError = computed(() => page.props.flash?.error ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const mobileNavOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#f8fafc]">
        <div v-if="flashError || flashSuccess" class="fixed right-4 top-4 z-50 max-w-sm">
            <div
                v-if="flashError"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-lg"
            >
                {{ flashError }}
            </div>
            <div
                v-if="flashSuccess"
                class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg"
            >
                {{ flashSuccess }}
            </div>
        </div>

        <div
            v-if="mobileNavOpen"
            class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"
            @click="mobileNavOpen = false"
        />

        <aside
            class="fixed inset-y-0 left-0 z-50 w-[260px] border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <SidebarNav :mobile-open="mobileNavOpen" @close-mobile="mobileNavOpen = false" />
        </aside>

        <div class="min-h-screen lg:pl-[260px]">
            <TopBar :on-open-mobile-nav="() => (mobileNavOpen = true)" />

            <main class="px-4 py-6 sm:px-6">
                <slot />
            </main>
        </div>
    </div>
</template>
