<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const menu = computed(() => page.props.menu ?? []);
const tasks = computed(() => page.props.tasks ?? []);
const flashError = computed(() => page.props.flash?.error ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const currentPath = computed(() => page.url.split('?')[0]);
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

        <div class="flex min-h-screen">
            <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:block">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-700">Wonderland ERP</p>
                    <p class="mt-1 text-sm text-slate-500">Staff portal</p>
                </div>
                <div v-if="tasks.length" class="border-b border-slate-200 p-4">
                    <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Quick tasks</p>
                    <nav class="space-y-1">
                        <Link
                            v-for="task in tasks"
                            :key="task.key"
                            :href="task.href"
                            class="block rounded-lg px-3 py-2 text-sm font-medium text-teal-800 transition hover:bg-teal-50"
                        >
                            {{ task.label }}
                        </Link>
                    </nav>
                </div>
                <nav class="space-y-1 p-4">
                    <Link
                        v-for="item in menu"
                        :key="item.key"
                        :href="item.href"
                        class="flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            currentPath === item.href || (item.href !== '/' && currentPath.startsWith(item.href))
                                ? 'bg-teal-50 text-teal-900'
                                : 'text-slate-700 hover:bg-teal-50 hover:text-teal-900'
                        "
                    >
                        <span>{{ item.label }}</span>
                        <span
                            v-if="item.phase > 0 && item.key !== 'dashboard'"
                            class="rounded bg-slate-100 px-2 py-0.5 text-[10px] uppercase text-slate-500"
                        >
                            P{{ item.phase }}
                        </span>
                    </Link>
                </nav>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="border-b border-slate-200 bg-white px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-lg font-semibold text-slate-900">{{ title || 'Dashboard' }}</h1>
                            <p v-if="user" class="truncate text-sm text-slate-500">
                                Signed in as {{ user.name || user.username }}
                            </p>
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
                    <div v-if="tasks.length" class="mt-4 flex gap-2 overflow-x-auto pb-1 lg:hidden">
                        <Link
                            v-for="task in tasks"
                            :key="task.key"
                            :href="task.href"
                            class="shrink-0 rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-900 ring-1 ring-teal-200"
                        >
                            {{ task.label }}
                        </Link>
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
