<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';
import { normalizePath, useInertiaNavigation } from '../composables/useInertiaNavigation.js';
import {
    readExpandedKeys,
    readSidebarScroll,
    writeExpandedKeys,
    writeSidebarScroll,
} from '../composables/useSidebarState.js';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    BedDouble,
    BookOpen,
    CalendarRange,
    ChevronRight,
    ClipboardList,
    Coffee,
    FileBarChart,
    LayoutDashboard,
    LayoutGrid,
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
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close-mobile']);

const page = usePage();
const { pendingPath, markPendingNavigation } = useInertiaNavigation();
const navigation = computed(() => page.props.navigation ?? []);
const currentPath = computed(() => normalizePath(page.url));
const activePath = computed(() => pendingPath.value ?? currentPath.value);

const allNavPaths = computed(() => {
    const paths = [];

    for (const section of navigation.value ?? []) {
        for (const item of section.items ?? []) {
            if (item.href) {
                paths.push(normalizePath(item.href));
            }

            for (const child of item.children ?? []) {
                if (child.href) {
                    paths.push(normalizePath(child.href));
                }
            }
        }
    }

    return [...new Set(paths)];
});

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

const expandedKeys = ref(readExpandedKeys());
const flyoutKey = ref(null);
const navScrollRef = ref(null);
let removeRouterListener = null;

function restoreSidebarScroll() {
    const saved = readSidebarScroll();

    if (navScrollRef.value === null || saved === null || Number.isNaN(saved)) {
        return;
    }

    navScrollRef.value.scrollTop = saved;
}

function onSidebarScroll() {
    if (navScrollRef.value !== null) {
        writeSidebarScroll(navScrollRef.value.scrollTop);
    }
}

onMounted(() => {
    restoreSidebarScroll();

    removeRouterListener = router.on('finish', () => {
        nextTick(restoreSidebarScroll);
    });
});

onUnmounted(() => {
    if (typeof removeRouterListener === 'function') {
        removeRouterListener();
    }
});

function resolveIcon(name) {
    return iconMap[name] ?? LayoutGrid;
}

function isActive(href) {
    const target = normalizePath(href);

    if (!target) {
        return false;
    }

    if (activePath.value === target) {
        return true;
    }

    if (target === '/' || !activePath.value.startsWith(`${target}/`)) {
        return false;
    }

    const hasMoreSpecificNavMatch = allNavPaths.value.some(
        (path) =>
            path !== target &&
            path.startsWith(`${target}/`) &&
            (activePath.value === path || activePath.value.startsWith(`${path}/`)),
    );

    return !hasMoreSpecificNavMatch;
}

function childIsActive(child) {
    for (const prefix of child.active_prefixes ?? []) {
        if (activePath.value === prefix || activePath.value.startsWith(`${prefix}/`)) {
            return true;
        }
    }

    return isActive(child.href);
}

function itemIsActive(item) {
    for (const prefix of item.active_prefixes ?? []) {
        if (activePath.value === prefix || activePath.value.startsWith(`${prefix}/`)) {
            return true;
        }
    }

    if (item.href && isActive(item.href)) {
        return true;
    }

    return (item.children ?? []).some((child) => childIsActive(child));
}

function syncExpandedFromRoute() {
    if (props.collapsed) {
        return;
    }

    const keys = new Set(expandedKeys.value);

    navigation.value.forEach((section) => {
        section.items?.forEach((item) => {
            if ((item.children ?? []).length > 0 && itemIsActive(item)) {
                keys.add(item.key);
            }
        });
    });

    if (setsEqual(keys, expandedKeys.value)) {
        return;
    }

    expandedKeys.value = keys;
    writeExpandedKeys(keys);
    nextTick(restoreSidebarScroll);
}

function setsEqual(a, b) {
    if (a.size !== b.size) {
        return false;
    }

    for (const value of a) {
        if (!b.has(value)) {
            return false;
        }
    }

    return true;
}

watch([navigation, activePath, () => props.collapsed], syncExpandedFromRoute, { immediate: true });

watch(() => props.collapsed, (value) => {
    if (value) {
        flyoutKey.value = null;
    }
});

function toggleExpanded(key) {
    if (props.collapsed) {
        flyoutKey.value = flyoutKey.value === key ? null : key;
        return;
    }

    onSidebarScroll();

    const keys = new Set(expandedKeys.value);

    if (keys.has(key)) {
        keys.delete(key);
    } else {
        keys.add(key);
    }

    expandedKeys.value = keys;
    writeExpandedKeys(keys);

    nextTick(restoreSidebarScroll);
}

function isExpanded(key) {
    return expandedKeys.value.has(key);
}

function itemClasses(active) {
    return [
        active ? 'wh-sidebar-link-active' : 'wh-sidebar-link-inactive',
        props.collapsed ? 'justify-center px-2' : '',
    ];
}

function iconClasses(active) {
    return active ? 'text-white' : 'text-slate-400 group-hover:text-slate-600';
}

function childClasses(active) {
    return active ? 'wh-sidebar-sublink-active' : 'wh-sidebar-sublink-inactive';
}

function closeFlyout() {
    flyoutKey.value = null;
}

function onNavClick(href) {
    onSidebarScroll();
    markPendingNavigation(href);
    emit('close-mobile');
}

function onChildNavigate(href) {
    onSidebarScroll();
    markPendingNavigation(href);
    emit('close-mobile');
    closeFlyout();
}
</script>

<template>
    <div class="flex h-full flex-col bg-white">
        <div
            class="wh-shell-header px-4"
            :class="collapsed ? 'lg:justify-center lg:px-2' : ''"
        >
            <div
                class="flex w-full items-center justify-between gap-2"
                :class="collapsed ? 'lg:justify-center' : ''"
            >
                <div class="flex min-w-0 items-center gap-3" :class="collapsed ? 'lg:justify-center lg:gap-0' : ''">
                    <AppLogo
                        :variant="collapsed ? 'mark' : 'full'"
                        :show-name="!collapsed"
                        size="md"
                    />
                </div>

                <button
                    type="button"
                    class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden"
                    aria-label="Close menu"
                    @click="emit('close-mobile')"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>
        </div>

        <nav
            ref="navScrollRef"
            class="sidebar-scroll min-h-0 flex-1 overflow-y-auto pb-8"
            @scroll="onSidebarScroll"
        >
            <div v-for="section in navigation" :key="section.key">
                <p
                    v-show="!collapsed"
                    class="px-4 pb-2 pt-4 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400"
                >
                    {{ section.label }}
                </p>
                <div v-show="collapsed" class="mx-3 mt-3 border-t border-slate-100 first:mt-4" />

                <ul class="space-y-0.5 px-3" :class="collapsed ? 'lg:px-2' : ''">
                    <li v-for="item in section.items" :key="item.key" class="relative">
                        <Link
                            v-if="!item.children?.length && item.href"
                            :href="item.href"
                            class="group wh-sidebar-link"
                            :class="itemClasses(isActive(item.href))"
                            :title="collapsed ? item.label : undefined"
                            @click="onNavClick(item.href)"
                        >
                            <component
                                :is="resolveIcon(item.icon)"
                                class="h-[18px] w-[18px] shrink-0"
                                :class="iconClasses(isActive(item.href))"
                                :stroke-width="1.75"
                            />
                            <span v-show="!collapsed" class="flex-1 truncate">{{ item.label }}</span>
                            <ChevronRight
                                v-if="!collapsed"
                                class="h-4 w-4 shrink-0 transition"
                                :class="isActive(item.href) ? 'text-white opacity-100' : 'text-slate-300 opacity-0 group-hover:opacity-100'"
                            />
                        </Link>

                        <div v-else class="relative">
                            <button
                                type="button"
                                class="wh-sidebar-link w-full text-left"
                                :class="itemClasses(itemIsActive(item))"
                                :title="collapsed ? item.label : undefined"
                                @click="toggleExpanded(item.key)"
                            >
                                <component
                                    :is="resolveIcon(item.icon)"
                                    class="h-[18px] w-[18px] shrink-0"
                                    :class="iconClasses(itemIsActive(item))"
                                    :stroke-width="1.75"
                                />
                                <span v-show="!collapsed" class="flex-1 truncate">{{ item.label }}</span>
                                <ChevronRight
                                    v-if="!collapsed"
                                    class="h-4 w-4 shrink-0 transition-transform duration-200"
                                    :class="[
                                        isExpanded(item.key) ? 'rotate-90' : '',
                                        itemIsActive(item) ? 'text-white' : 'text-slate-400',
                                    ]"
                                />
                            </button>

                            <ul
                                v-if="!collapsed && isExpanded(item.key)"
                                class="ml-6 mt-0.5 space-y-0.5 border-l border-slate-200 pl-3"
                            >
                                <li v-for="child in item.children" :key="child.key">
                                    <Link
                                        :href="child.href"
                                        class="wh-sidebar-sublink"
                                        :class="childClasses(childIsActive(child))"
                                        @click="onNavClick(child.href)"
                                    >
                                        {{ child.label }}
                                    </Link>
                                </li>
                            </ul>

                            <div
                                v-if="collapsed && flyoutKey === item.key"
                                class="absolute left-full top-0 z-50 ml-2 min-w-[200px] rounded-xl border border-slate-200 bg-white py-2 shadow-lg"
                            >
                                <p class="border-b border-slate-100 px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    {{ item.label }}
                                </p>
                                <Link
                                    v-for="child in item.children"
                                    :key="child.key"
                                    :href="child.href"
                                    class="wh-sidebar-flyout-link"
                                    :class="childClasses(childIsActive(child))"
                                    @click="onChildNavigate(child.href)"
                                >
                                    {{ child.label }}
                                </Link>
                            </div>
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
