<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '../../Components/EmptyState.vue';
import PageHeader from '../../Components/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    filter: { type: String, default: 'all' },
    notifications: { type: Array, default: () => [] },
    unread_count: { type: Number, default: 0 },
});

const tabs = [
    { key: 'all', label: 'All' },
    { key: 'unread', label: 'Unread' },
];

const hasUnread = computed(() => props.unread_count > 0);

function switchFilter(key) {
    router.get('/notifications', key === 'all' ? {} : { filter: key }, { preserveScroll: true });
}

function markRead(id) {
    router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
}

function markAllRead() {
    router.post('/notifications/read-all', {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Notifications">
        <PageHeader
            title="Notifications"
            :subtitle="hasUnread ? `${unread_count} unread` : 'You are all caught up'"
        >
            <template v-if="hasUnread" #actions>
                <button type="button" class="wh-btn-secondary" @click="markAllRead">
                    Mark all as read
                </button>
            </template>
        </PageHeader>

        <section class="wh-card mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    :class="filter === tab.key ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700'"
                    @click="switchFilter(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>
        </section>

        <section class="wh-card overflow-hidden">
            <EmptyState
                v-if="notifications.length === 0"
                title="No notifications here"
                :description="filter === 'unread' ? 'You have read everything in this view.' : 'New approvals and alerts will show up when they need your attention.'"
                variant="inbox"
            />

            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="item in notifications"
                    :key="item.id"
                    class="flex items-start justify-between gap-4 px-5 py-4"
                    :class="item.read_at ? 'bg-white' : 'bg-teal-50/40'"
                >
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            {{ item.category_label }}
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ item.title }}</p>
                        <p v-if="item.body" class="mt-1 text-sm text-slate-600">{{ item.body }}</p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-2">
                        <Link :href="item.href" class="wh-btn-secondary text-xs">Open</Link>
                        <button
                            v-if="!item.read_at"
                            type="button"
                            class="text-xs font-medium text-teal-700 hover:underline"
                            @click="markRead(item.id)"
                        >
                            Mark read
                        </button>
                    </div>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
