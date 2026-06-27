<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import EmptyState from './EmptyState.vue';

const page = usePage();
const open = ref(false);
const panelRef = ref(null);
const refreshing = ref(false);

const pollIntervalSeconds = computed(() => {
    const value = Number(page.props.shell?.notification_poll_seconds ?? 120);

    return Number.isFinite(value) && value >= 30 ? value : 120;
});

const summary = computed(() => page.props.notifications ?? { unread_count: 0, items: [] });
const unreadCount = computed(() => Number(summary.value.unread_count ?? 0));
const items = computed(() => summary.value.items ?? []);

const badgeLabel = computed(() => {
    if (unreadCount.value <= 0) {
        return '';
    }

    return unreadCount.value > 99 ? '99+' : String(unreadCount.value);
});

function togglePanel() {
    open.value = !open.value;
}

function closePanel() {
    open.value = false;
}

function refreshNotifications() {
    if (refreshing.value) {
        return;
    }

    refreshing.value = true;

    router.reload({
        only: ['notifications'],
        data: { refresh_notifications: true },
        preserveScroll: true,
        onFinish: () => {
            refreshing.value = false;
        },
    });
}

function markAllRead() {
    router.post('/notifications/read-all', {}, {
        preserveScroll: true,
        onSuccess: () => {
            closePanel();
        },
    });
}

function onDocumentClick(event) {
    if (!open.value) {
        return;
    }

    if (panelRef.value?.contains(event.target)) {
        return;
    }

    closePanel();
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
        refreshNotifications();
    }
}

let pollTimer = null;

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('visibilitychange', onVisibilityChange);

    pollTimer = window.setInterval(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        refreshNotifications();
    }, pollIntervalSeconds.value * 1000);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('visibilitychange', onVisibilityChange);

    if (pollTimer !== null) {
        window.clearInterval(pollTimer);
    }
});
</script>

<template>
    <div ref="panelRef" class="relative hidden sm:block">
        <button
            type="button"
            class="wh-icon-btn relative"
            :aria-label="unreadCount > 0 ? `${unreadCount} unread notifications` : 'Notifications'"
            :aria-expanded="open"
            @click.stop="togglePanel"
        >
            <Bell class="h-[18px] w-[18px]" />
            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white ring-2 ring-white"
            >
                {{ badgeLabel }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full z-50 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Notifications</p>
                    <p class="text-xs text-slate-500">
                        {{ unreadCount > 0 ? `${unreadCount} unread` : 'You are all caught up' }}
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100"
                        :disabled="refreshing"
                        aria-label="Refresh notifications"
                        @click="refreshNotifications"
                    >
                        <RefreshCw class="h-3.5 w-3.5" :class="refreshing ? 'animate-spin' : ''" />
                    </button>
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-teal-700 hover:bg-teal-50"
                        @click="markAllRead"
                    >
                        <CheckCheck class="h-3.5 w-3.5" />
                        Mark all read
                    </button>
                </div>
            </div>

            <div v-if="items.length === 0" class="px-2 py-2">
                <EmptyState
                    title="No new notifications"
                    description="Approvals and alerts for your role will appear here."
                    variant="inbox"
                    compact
                />
            </div>

            <ul v-else class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                <li v-for="item in items" :key="item.id">
                    <Link
                        :href="item.href"
                        class="block px-4 py-3 hover:bg-slate-50"
                        @click="closePanel"
                    >
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            {{ item.category_label }}
                        </p>
                        <p class="mt-0.5 text-sm font-medium text-slate-900">{{ item.title }}</p>
                        <p v-if="item.body" class="mt-0.5 text-xs text-slate-500">{{ item.body }}</p>
                    </Link>
                </li>
            </ul>

            <div class="border-t border-slate-100 px-4 py-2.5">
                <Link
                    href="/notifications"
                    class="text-sm font-medium text-teal-700 hover:underline"
                    @click="closePanel"
                >
                    View all notifications
                </Link>
            </div>
        </div>
    </div>
</template>
