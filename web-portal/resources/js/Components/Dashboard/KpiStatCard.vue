<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ArrowDownCircle,
    ArrowUpCircle,
    BedDouble,
    BookOpen,
    CalendarRange,
    ClipboardList,
    Landmark,
    LayoutGrid,
    Package,
    Receipt,
    ShieldCheck,
    TrendingUp,
    Truck,
    UserCog,
    Users,
    UsersRound,
    UtensilsCrossed,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    icon: { type: String, default: 'layout-grid' },
    tone: { type: String, default: 'indigo' },
    href: { type: String, default: '' },
    breakdown: { type: Array, default: () => [] },
});

const iconMap = {
    'layout-grid': LayoutGrid,
    'bed-double': BedDouble,
    'users-round': UsersRound,
    'utensils-crossed': UtensilsCrossed,
    package: Package,
    truck: Truck,
    'clipboard-list': ClipboardList,
    wallet: Wallet,
    'book-open': BookOpen,
    'arrow-down-circle': ArrowDownCircle,
    'arrow-up-circle': ArrowUpCircle,
    users: Users,
    'user-cog': UserCog,
    'shield-check': ShieldCheck,
    'calendar-range': CalendarRange,
    'trending-up': TrendingUp,
    landmark: Landmark,
    receipt: Receipt,
};

const Icon = computed(() => iconMap[props.icon] ?? LayoutGrid);

const toneClasses = computed(() => {
    const map = {
        indigo: 'bg-indigo-50 text-indigo-600 ring-indigo-100',
        emerald: 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        teal: 'bg-teal-50 text-teal-600 ring-teal-100',
        sky: 'bg-sky-50 text-sky-600 ring-sky-100',
        amber: 'bg-amber-50 text-amber-600 ring-amber-100',
        rose: 'bg-rose-50 text-rose-600 ring-rose-100',
        slate: 'bg-slate-100 text-slate-600 ring-slate-200',
    };

    return map[props.tone] ?? map.indigo;
});

function breakdownClass(tone) {
    const map = {
        success: 'text-emerald-700 bg-emerald-50',
        danger: 'text-red-700 bg-red-50',
        warning: 'text-amber-700 bg-amber-50',
        info: 'text-sky-700 bg-sky-50',
        muted: 'text-slate-600 bg-slate-100',
    };

    return map[tone] ?? map.muted;
}
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        class="wh-dash-kpi block transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ value }}</p>
            </div>
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl ring-1 ring-inset" :class="toneClasses">
                <component :is="Icon" class="h-5 w-5" :stroke-width="1.75" />
            </span>
        </div>

        <div v-if="breakdown.length" class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
            <span
                v-for="item in breakdown"
                :key="item.label"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium"
                :class="breakdownClass(item.tone)"
            >
                {{ item.label }}: {{ item.value }}
            </span>
        </div>
    </Link>

    <div v-else class="wh-dash-kpi">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ value }}</p>
            </div>
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl ring-1 ring-inset" :class="toneClasses">
                <component :is="Icon" class="h-5 w-5" :stroke-width="1.75" />
            </span>
        </div>

        <div v-if="breakdown.length" class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
            <span
                v-for="item in breakdown"
                :key="item.label"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium"
                :class="breakdownClass(item.tone)"
            >
                {{ item.label }}: {{ item.value }}
            </span>
        </div>
    </div>
</template>
