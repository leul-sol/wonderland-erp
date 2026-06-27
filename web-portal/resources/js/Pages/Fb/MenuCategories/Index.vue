<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const createForm = useForm({
    name: '',
    display_order: '',
});

const columns = [
    { key: 'name', label: 'Category' },
    { key: 'display_order', label: 'Order', class: 'text-right' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function submitCreate() {
    createForm.post('/fb/menu-categories', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

async function toggleActive(category) {
    const deactivating = category.is_active !== false;
    const ok = await confirmAction({
        title: deactivating ? 'Deactivate category' : 'Activate category',
        message: deactivating
            ? `Hide "${category.name}" from new menu assignments?`
            : `Restore "${category.name}" for menu items?`,
        confirmLabel: deactivating ? 'Deactivate' : 'Activate',
    });

    if (!ok) {
        return;
    }

    useForm({ is_active: !deactivating }).put(`/fb/menu-categories/${category.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Menu categories">
        <PageHeader title="Menu categories" subtitle="Organize items for service and reporting">
            <template #actions>
                <Link href="/fb/settings" class="wh-btn-secondary">Catalog admin</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="categories">
        <form class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add category</h3>
            <div class="flex flex-wrap items-end gap-3">
                <div class="min-w-[200px] flex-1">
                    <label for="name" class="mb-1 block text-xs font-medium text-slate-600">Name</label>
                    <input id="name" v-model="createForm.name" type="text" required class="wh-input" />
                </div>
                <div class="w-28">
                    <label for="display_order" class="mb-1 block text-xs font-medium text-slate-600">Order</label>
                    <input id="display_order" v-model.number="createForm.display_order" type="number" min="0" class="wh-input" />
                </div>
                <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Add</button>
            </div>
        </form>

        <DataTable list-title="Categories" :columns="columns" :rows="categories" empty-message="No categories yet.">
            <template #cell-is_active="{ row }">
                <StatusBadge :status="row.is_active === false ? 'inactive' : 'active'" />
            </template>
            <template #cell-actions="{ row }">
                <button type="button" class="wh-btn-secondary text-xs" @click="toggleActive(row)">
                    {{ row.is_active === false ? 'Activate' : 'Deactivate' }}
                </button>
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
