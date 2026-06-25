<script setup>
import { Link } from '@inertiajs/vue3';
import {
    BedDouble,
    BookOpen,
    Coffee,
    LayoutGrid,
    Package,
    ShieldCheck,
    UserCog,
    UsersRound,
    UtensilsCrossed,
    Wallet,
} from 'lucide-vue-next';

defineProps({
    links: { type: Array, default: () => [] },
});

const iconMap = {
    'layout-grid': LayoutGrid,
    'bed-double': BedDouble,
    'users-round': UsersRound,
    'utensils-crossed': UtensilsCrossed,
    package: Package,
    coffee: Coffee,
    'user-cog': UserCog,
    wallet: Wallet,
    'book-open': BookOpen,
    'shield-check': ShieldCheck,
};

const tones = ['bg-indigo-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500', 'bg-sky-500', 'bg-violet-500'];

function resolveIcon(name) {
    return iconMap[name] ?? LayoutGrid;
}
</script>

<template>
    <div class="grid grid-cols-3 gap-4 sm:grid-cols-6">
        <Link
            v-for="(link, index) in links"
            :key="link.href"
            :href="link.href"
            class="group flex flex-col items-center gap-2 text-center"
        >
            <span
                class="flex h-14 w-14 items-center justify-center rounded-full text-white shadow-sm transition group-hover:scale-105"
                :class="tones[index % tones.length]"
            >
                <component :is="resolveIcon(link.icon)" class="h-6 w-6" :stroke-width="1.75" />
            </span>
            <span class="text-xs font-medium text-slate-600 group-hover:text-slate-900">{{ link.label }}</span>
        </Link>
    </div>
</template>
