<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, LogOut, Menu, Search, Sun } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineProps({
    sidebarCollapsed: {
        type: Boolean,
        default: false,
    },
    onToggleSidebar: {
        type: Function,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const userMenuOpen = ref(false);

const initials = computed(() => {
    const name = user.value?.name || user.value?.username || '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

function reloadPage() {
    router.reload({ preserveScroll: true });
}
</script>

<template>
    <header class="sticky top-0 z-30 bg-white">
        <div class="wh-shell-header gap-3 px-4 sm:gap-4 sm:px-6">
            <button
                type="button"
                class="wh-icon-btn"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                @click="onToggleSidebar?.()"
            >
                <Menu class="h-[18px] w-[18px]" />
            </button>

            <div class="relative min-w-0 flex-1 sm:max-w-md">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    class="wh-topbar-search"
                    placeholder="Search modules, pages..."
                    aria-label="Search"
                />
            </div>

            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                <button type="button" class="wh-icon-btn hidden sm:inline-flex" aria-label="Theme" @click="reloadPage">
                    <Sun class="h-[18px] w-[18px]" />
                </button>

                <button type="button" class="wh-icon-btn relative hidden sm:inline-flex" aria-label="Notifications">
                    <Bell class="h-[18px] w-[18px]" />
                    <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white" />
                </button>

                <div class="relative">
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-sm font-semibold text-white"
                        aria-label="User menu"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        {{ initials }}
                    </button>

                    <div
                        v-if="userMenuOpen"
                        class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-2 shadow-lg"
                    >
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">
                                {{ user?.name || user?.username }}
                            </p>
                            <p v-if="user?.email" class="truncate text-xs text-slate-500">{{ user.email }}</p>
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50"
                            @click="userMenuOpen = false"
                        >
                            <LogOut class="h-4 w-4" />
                            Log out
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
