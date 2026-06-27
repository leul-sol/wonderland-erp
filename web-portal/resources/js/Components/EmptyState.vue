<script setup>
defineProps({
    title: {
        type: String,
        default: 'Nothing here yet',
    },
    description: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'default',
    },
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center text-center"
        :class="compact ? 'px-4 py-6' : 'px-6 py-10'"
    >
        <div
            class="mb-4 flex items-center justify-center rounded-full bg-gradient-to-b from-teal-50 to-slate-50"
            :class="compact ? 'h-20 w-20' : 'h-24 w-24'"
        >
            <svg
                viewBox="0 0 120 96"
                class="text-teal-700"
                :class="compact ? 'h-14 w-14' : 'h-16 w-16'"
                aria-hidden="true"
            >
                <template v-if="variant === 'table'">
                    <rect x="18" y="20" width="84" height="56" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" />
                    <line x1="30" y1="36" x2="90" y2="36" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                    <line x1="30" y1="48" x2="74" y2="48" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                    <line x1="30" y1="60" x2="82" y2="60" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                    <circle cx="88" cy="68" r="18" fill="#ccfbf1" stroke="#14b8a6" stroke-width="2" />
                    <path d="M82 68h12M88 62v12" stroke="#0f766e" stroke-width="2" stroke-linecap="round" />
                </template>

                <template v-else-if="variant === 'search'">
                    <circle cx="52" cy="44" r="22" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" />
                    <circle cx="52" cy="44" r="12" fill="none" stroke="#94a3b8" stroke-width="2" />
                    <line x1="68" y1="60" x2="86" y2="78" stroke="#0f766e" stroke-width="3" stroke-linecap="round" />
                    <path d="M38 78c6-8 14-12 24-12" fill="none" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                </template>

                <template v-else-if="variant === 'inbox'">
                    <path d="M24 30h72a6 6 0 0 1 6 6v34a6 6 0 0 1-6 6H24a6 6 0 0 1-6-6V36a6 6 0 0 1 6-6z" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" />
                    <path d="M18 38l30 20a6 6 0 0 0 6.6 0l30-20" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linejoin="round" />
                    <circle cx="88" cy="68" r="14" fill="#ccfbf1" stroke="#14b8a6" stroke-width="2" />
                    <path d="M88 62v12M82 68h12" stroke="#0f766e" stroke-width="2" stroke-linecap="round" />
                </template>

                <template v-else-if="variant === 'chart'">
                    <rect x="20" y="58" width="14" height="24" rx="3" fill="#99f6e4" />
                    <rect x="42" y="46" width="14" height="36" rx="3" fill="#5eead4" />
                    <rect x="64" y="34" width="14" height="48" rx="3" fill="#2dd4bf" />
                    <rect x="86" y="50" width="14" height="32" rx="3" fill="#14b8a6" />
                    <line x1="16" y1="82" x2="104" y2="82" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round" />
                    <path d="M24 24h72" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                    <path d="M24 32h48" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                </template>

                <template v-else-if="variant === 'folder'">
                    <path d="M20 34h28l8 10h44a8 8 0 0 1 8 8v24a8 8 0 0 1-8 8H20a8 8 0 0 1-8-8V42a8 8 0 0 1 8-8z" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" stroke-linejoin="round" />
                    <circle cx="84" cy="68" r="14" fill="#ccfbf1" stroke="#14b8a6" stroke-width="2" />
                    <path d="M84 62v12M78 68h12" stroke="#0f766e" stroke-width="2" stroke-linecap="round" />
                </template>

                <template v-else>
                    <rect x="28" y="18" width="64" height="60" rx="10" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2" />
                    <circle cx="60" cy="44" r="16" fill="#ccfbf1" stroke="#14b8a6" stroke-width="2" />
                    <path d="M60 36v16M52 44h16" stroke="#0f766e" stroke-width="2" stroke-linecap="round" />
                    <path d="M40 72h40" stroke="#e2e8f0" stroke-width="2" stroke-linecap="round" />
                </template>
            </svg>
        </div>

        <h3 class="text-sm font-semibold text-slate-900">
            {{ title }}
        </h3>

        <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
