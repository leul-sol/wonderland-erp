<script setup>
import SidebarNav from '../Components/SidebarNav.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const flashError = computed(() => page.props.flash?.error ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const mobileNavOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-100">
        <div v-if="flashError || flashSuccess" class="fixed right-4 top-4 z-50 max-w-sm">
            <div
                v-if="flashError"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-lg"
            >
                {{ flashError }}
            </div>
            <div
                v-if="flashSuccess"
                class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg"
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
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden"
                            aria-label="Open menu"
                            @click="mobileNavOpen = true"
                        >
                            <Menu class="h-5 w-5" />
                        </button>
                        <div class="min-w-0">
                            <h1 class="truncate text-lg font-semibold text-slate-900">{{ title || 'Dashboard' }}</h1>
                            <p v-if="user" class="truncate text-sm text-slate-500">
                                Signed in as {{ user.name || user.username }}
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Log out
                    </Link>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6">
                <slot />
            </main>
        </div>
    </div>
</template>
