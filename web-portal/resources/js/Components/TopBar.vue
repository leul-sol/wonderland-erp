<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, Menu, RefreshCw, Search } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import NotificationBell from './NotificationBell.vue';

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
const searchQuery = ref('');
const searchOpen = ref(false);
const searchInput = ref(null);

const initials = computed(() => {
    const name = user.value?.name || user.value?.username || '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
});

const searchItems = computed(() => {
    const items = [];

    for (const section of page.props.navigation ?? []) {
        for (const item of section.items ?? []) {
            if (item.href) {
                items.push({ label: item.label, href: item.href, group: section.label });
            }

            for (const child of item.children ?? []) {
                if (child.href) {
                    items.push({ label: child.label, href: child.href, group: item.label });
                }
            }
        }
    }

    for (const task of page.props.tasks ?? []) {
        if (task.href) {
            items.push({ label: task.label, href: task.href, group: 'Quick tasks' });
        }
    }

    return items;
});

const searchResults = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) {
        return [];
    }

    return searchItems.value
        .filter((item) => item.label.toLowerCase().includes(query) || item.group.toLowerCase().includes(query))
        .slice(0, 8);
});

function reloadPage() {
    router.reload({ preserveScroll: true });
}

function goToResult(href) {
    searchOpen.value = false;
    searchQuery.value = '';
    router.visit(href);
}

function onSearchKeydown(event) {
    if (event.key === 'Escape') {
        searchOpen.value = false;
        searchQuery.value = '';
    }

    if (event.key === 'Enter' && searchResults.value[0]) {
        goToResult(searchResults.value[0].href);
    }
}

function onDocumentClick(event) {
    if (!event.target.closest?.('[data-wh-nav-search]')) {
        searchOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
});
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

            <div class="relative min-w-0 flex-1 sm:max-w-md" data-wh-nav-search>
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    ref="searchInput"
                    v-model="searchQuery"
                    type="search"
                    class="wh-topbar-search"
                    placeholder="Search modules, pages..."
                    aria-label="Search navigation"
                    @focus="searchOpen = true"
                    @keydown="onSearchKeydown"
                />

                <div
                    v-if="searchOpen && searchQuery.trim()"
                    class="absolute left-0 right-0 top-full z-50 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                >
                    <p v-if="searchResults.length === 0" class="px-4 py-3 text-sm text-slate-500">No pages match your search.</p>
                    <button
                        v-for="result in searchResults"
                        :key="`${result.href}-${result.label}`"
                        type="button"
                        class="flex w-full flex-col items-start px-4 py-2.5 text-left hover:bg-slate-50"
                        @click="goToResult(result.href)"
                    >
                        <span class="text-sm font-medium text-slate-900">{{ result.label }}</span>
                        <span class="text-xs text-slate-500">{{ result.group }}</span>
                    </button>
                </div>
            </div>

            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    class="wh-icon-btn hidden sm:inline-flex"
                    aria-label="Refresh page"
                    title="Refresh page"
                    @click="reloadPage"
                >
                    <RefreshCw class="h-[18px] w-[18px]" />
                </button>

                <NotificationBell />

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
