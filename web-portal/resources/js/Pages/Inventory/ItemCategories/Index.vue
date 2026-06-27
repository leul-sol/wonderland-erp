<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const createForm = useForm({
    name: '',
    description: '',
});

const columns = [
    { key: 'name', label: 'Category' },
    { key: 'description', label: 'Description' },
    { key: 'actions', label: '', class: 'text-right' },
];

function submitCreate() {
    createForm.post('/inventory/item-categories', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

async function deactivate(category) {
    const ok = await confirmAction({
        title: 'Deactivate category',
        message: `Deactivate "${category.name}"? Items keep their category reference.`,
        confirmLabel: 'Deactivate',
    });

    if (!ok) {
        return;
    }

    useForm({}).delete(`/inventory/item-categories/${category.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Item categories">
        <PageHeader title="Item categories" subtitle="Organize inventory for procurement and reporting">
            <template #actions>
                <Link href="/inventory/items" class="wh-btn-secondary">Items</Link>
                <Link href="/inventory/items?open=create" class="wh-btn-primary">New item</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="categories">
        <form class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add category</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                <input v-model="createForm.name" type="text" required class="wh-input" placeholder="Category name" />
                <input v-model="createForm.description" type="text" class="wh-input" placeholder="Description (optional)" />
                <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Add</button>
            </div>
        </form>

        <DataTable list-title="Categories" :columns="columns" :rows="categories" empty-message="No categories yet.">
            <template #cell-description="{ row }">
                {{ row.description ?? '—' }}
            </template>
            <template #cell-actions="{ row }">
                <button type="button" class="wh-btn-secondary text-xs" @click="deactivate(row)">Deactivate</button>
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
