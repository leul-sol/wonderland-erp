<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    BedDouble,
    BookOpen,
    Building2,
    CalendarRange,
    ChevronDown,
    ChevronRight,
    ClipboardList,
    Coffee,
    FileBarChart,
    LayoutDashboard,
    LayoutGrid,
    Menu,
    Package,
    PieChart,
    ScrollText,
    ShieldCheck,
    Truck,
    UserCog,
    Users,
    UsersRound,
    UtensilsCrossed,
    Wallet,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

defineProps({
    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close-mobile']);

const page = usePage();
const navigation = computed(() => page.props.navigation ?? []);
const currentPath = computed(() => page.url.split('?')[0]);

const iconMap = {
    'layout-grid': LayoutGrid,
    'bed-double': BedDouble,
    'users-round': UsersRound,
    'utensils-crossed': UtensilsCrossed,
    package: Package,
    truck: Truck,
    'clipboard-list': ClipboardList,
    coffee: Coffee,
    'user-cog': UserCog,
    wallet: Wallet,
    'file-bar-chart': FileBarChart,
    'book-open': BookOpen,
    'arrow-down-circle': ArrowDownCircle,
    'arrow-up-circle': ArrowUpCircle,
    'pie-chart': PieChart,
    'calendar-range': CalendarRange,
    'layout-dashboard': LayoutDashboard,
    users: Users,
    'shield-check': ShieldCheck,
    'scroll-text': ScrollText,
    zap: Zap,
};

const expandedKeys = ref(new Set());

function resolveIcon(name) {
    return iconMap[name] ?? LayoutGrid;
}

function isActive(href) {
    if (!href) {
        return false;
    }

    if (currentPath.value === href) {
        return true;
    }

    return href !== '/' && currentPath.value.startsWith(`${href}/`);
}

function itemIsActive(item) {
    if (item.href && isActive(item.href)) {
        return true;
    }

    return (item.children ?? []).some((child) => isActive(child.href));
}

function syncExpandedFromRoute() {
    const keys = new Set(expandedKeys.value);

    navigation.value.forEach((section) => {
        section.items?.forEach((item) => {
            if ((item.children ?? []).length > 0 && itemIsActive(item)) {
                keys.add(item.key);
            }
        });
    });

    expandedKeys.value = keys;
}

watch([navigation, currentPath], syncExpandedFromRoute, { immediate: true });

function toggleExpanded(key) {
    const keys = new Set(expandedKeys.value);

    if (keys.has(key)) {
        keys.delete(key);
    } else {
        keys.add(key);
    }

    expandedKeys.value = keys;
}

function isExpanded(key) {
    return expandedKeys.value.has(key);
}

function itemClasses(active) {
    return active
        ? 'bg-teal-50 text-teal-700'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
}

function iconClasses(active) {
    return active ? 'text-teal-700' : 'text-slate-400';
}
</script>

<template>
    <div class="flex h-full flex-col bg-white">
        <div class="shrink-0 border-b border-slate-200 px-4 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-700 text-base font-bold text-white"
                    >
                        W
                    </div>
                    <span class="truncate text-[17px] font-bold tracking-tight text-slate-800">Wonderland</span>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden"
                    aria-label="Close menu"
                    @click="emit('close-mobile')"
                >
                    <X class="h-5 w-5" />
                </button>
                <button
                    type="button"
                    class="hidden rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:inline-flex"
                    aria-label="Menu"
                >
                    <Menu class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div class="shrink-0 px-4 py-4">
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-lg bg-indigo-50 px-3 py-2.5 text-left transition hover:bg-indigo-100/80"
            >
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white text-indigo-600 shadow-sm">
                    <Building2 class="h-4 w-4" />
                </span>
                <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700">Wonderland Hotel</span>
                <ChevronDown class="h-4 w-4 shrink-0 text-slate-400" />
            </button>
        </div>

        <nav class="sidebar-scroll min-h-0 flex-1 overflow-y-auto overflow-x-hidden pb-8">
            <div v-for="section in navigation" :key="section.key">
                <p class="px-4 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">
                    {{ section.label }}
                </p>

                <ul class="space-y-0.5 px-3">
                    <li v-for="item in section.items" :key="item.key">
                        <Link
                            v-if="!item.children?.length && item.href"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium transition"
                            :class="itemClasses(isActive(item.href))"
                            @click="emit('close-mobile')"
                        >
                            <component
                                :is="resolveIcon(item.icon)"
                                class="h-[18px] w-[18px] shrink-0"
                                :class="iconClasses(isActive(item.href))"
                                :stroke-width="1.75"
                            />
                            <span class="flex-1 truncate">{{ item.label }}</span>
                            <span
                                v-if="section.key === 'modules' && item.phase > 0 && item.key !== 'dashboard'"
                                class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500"
                            >
                                P{{ item.phase }}
                            </span>
                            <ChevronRight
                                class="h-4 w-4 shrink-0 opacity-0 transition group-hover:opacity-100"
                                :class="isActive(item.href) ? 'text-teal-600 opacity-100' : 'text-slate-300'"
                            />
                        </Link>

                        <div v-else>
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-[13px] font-medium transition"
                                :class="itemClasses(itemIsActive(item))"
                                @click="toggleExpanded(item.key)"
                            >
                                <component
                                    :is="resolveIcon(item.icon)"
                                    class="h-[18px] w-[18px] shrink-0"
                                    :class="iconClasses(itemIsActive(item))"
                                    :stroke-width="1.75"
                                />
                                <span class="flex-1 truncate">{{ item.label }}</span>
                                <span
                                    v-if="section.key === 'modules' && item.phase > 0 && item.key !== 'dashboard'"
                                    class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-slate-500"
                                >
                                    P{{ item.phase }}
                                </span>
                                <ChevronRight
                                    class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
                                    :class="{ 'rotate-90 text-teal-600': isExpanded(item.key) }"
                                />
                            </button>

                            <ul
                                v-show="isExpanded(item.key)"
                                class="mt-0.5 space-y-0.5 border-l border-slate-200 pl-3 ml-6"
                            >
                                <li v-for="child in item.children" :key="child.key">
                                    <Link
                                        :href="child.href"
                                        class="block rounded-lg px-3 py-2 text-[13px] font-medium transition"
                                        :class="
                                            isActive(child.href)
                                                ? 'bg-teal-50 text-teal-700'
                                                : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'
                                        "
                                        @click="emit('close-mobile')"
                                    >
                                        {{ child.label }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</template>

<style scoped>
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgb(203 213 225) transparent;
}

.sidebar-scroll::-webkit-scrollbar {
    width: 5px;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background: rgb(203 213 225);
}
</style>
