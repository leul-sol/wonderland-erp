<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    sku: '',
    name: '',
    unit: 'each',
    unit_cost: '',
    category_id: props.categories[0]?.id ?? '',
    rotation_strategy: 'fifo',
    is_perishable: false,
    reorder_level: '',
});

function submit() {
    form.post('/inventory/items');
}
</script>

<template>
    <AppLayout title="New inventory item">
        <PageHeader title="New inventory item" subtitle="SKU master for stock, POs, and recipes">
            <template #actions>
                <Link href="/inventory/items" class="wh-btn-secondary">All items</Link>
            </template>
        </PageHeader>

        <form class="wh-card max-w-2xl p-6 space-y-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="sku" class="mb-1 block text-sm font-medium text-slate-700">SKU</label>
                    <input id="sku" v-model="form.sku" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="name" v-model="form.name" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="unit" class="mb-1 block text-sm font-medium text-slate-700">Unit</label>
                    <input id="unit" v-model="form.unit" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="unit_cost" class="mb-1 block text-sm font-medium text-slate-700">Unit cost (ETB)</label>
                    <MoneyField id="unit_cost" v-model="form.unit_cost" />
                </div>
                <div>
                    <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                    <select id="category_id" v-model="form.category_id" class="wh-input">
                        <option value="">None</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="reorder_level" class="mb-1 block text-sm font-medium text-slate-700">Reorder level</label>
                    <input id="reorder_level" v-model="form.reorder_level" type="number" min="0" step="0.001" class="wh-input" />
                </div>
                <div>
                    <label for="rotation_strategy" class="mb-1 block text-sm font-medium text-slate-700">Rotation</label>
                    <select id="rotation_strategy" v-model="form.rotation_strategy" class="wh-input">
                        <option value="fifo">FIFO</option>
                        <option value="fefo">FEFO</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input id="is_perishable" v-model="form.is_perishable" type="checkbox" class="rounded border-slate-300" />
                    <label for="is_perishable" class="text-sm text-slate-700">Perishable (expiry tracking)</label>
                </div>
            </div>
            <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create item</button>
        </form>
    </AppLayout>
</template>
