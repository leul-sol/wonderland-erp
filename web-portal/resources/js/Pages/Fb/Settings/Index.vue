<script setup>
import { Link } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const sections = [
    {
        title: 'Menu categories',
        description: 'Group items for POS and printed menus.',
        href: '/fb/menu-categories',
        cta: 'Manage categories',
    },
    {
        title: 'Menu items',
        description: 'Prices, staff meal rates, availability, and recipes.',
        href: '/fb/menu-items',
        cta: 'Manage items',
    },
    {
        title: 'Dining tables',
        description: 'Table numbers, capacity, and floor locations.',
        href: '/fb/dining-tables',
        cta: 'Manage tables',
    },
];
</script>

<template>
    <AppLayout title="F&B catalog admin">
        <PageHeader title="Restaurant catalog" subtitle="Back-office setup for menu and floor service">
            <template #actions>
                <Link href="/fb/menu" class="wh-btn-secondary">Floor menu</Link>
                <Link href="/fb/orders" class="wh-btn-secondary">Orders</Link>
            </template>
        </PageHeader>

        <div class="grid gap-4 md:grid-cols-3">
            <section v-for="section in sections" :key="section.href" class="wh-card flex flex-col p-5">
                <h3 class="text-base font-semibold text-slate-900">{{ section.title }}</h3>
                <p class="mt-2 flex-1 text-sm text-slate-600">{{ section.description }}</p>
                <Link :href="section.href" class="wh-btn-primary mt-4 w-full text-center">{{ section.cta }}</Link>
            </section>
        </div>
    </AppLayout>
</template>
