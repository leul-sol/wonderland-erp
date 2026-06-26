<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    item: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.item.name ?? '',
    unit: props.item.unit ?? 'each',
    unit_cost: props.item.unit_cost ?? '',
    category_id: props.item.category_id ?? '',
    rotation_strategy: props.item.rotation_strategy ?? 'fifo',
    is_perishable: props.item.is_perishable ?? false,
    reorder_level: props.item.reorder_level ?? '',
    is_active: props.item.is_active !== false,
});

function submit() {
    form.put(`/inventory/items/${props.item.id}`);
}

async function toggleActive() {
    const deactivating = form.is_active;
    const ok = await confirmAction({
        title: deactivating ? 'Deactivate item' : 'Activate item',
        message: deactivating ? `Hide ${props.item.sku} from new PO lines?` : `Restore ${props.item.sku} for procurement?`,
        confirmLabel: deactivating ? 'Deactivate' : 'Activate',
    });

    if (!ok) {
        return;
    }

    form.is_active = !deactivating;
    form.put(`/inventory/items/${props.item.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Edit ${item.sku}`">
        <PageHeader :title="`Edit ${item.name}`" :subtitle="item.sku">
            <template #actions>
                <StatusBadge :status="item.is_active === false ? 'inactive' : 'active'" />
                <Link :href="`/inventory/items/${item.id}`" class="wh-btn-secondary text-xs">View stock</Link>
            </template>
        </PageHeader>

        <form class="wh-card max-w-2xl p-6 space-y-4" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">SKU</label>
                    <input type="text" :value="item.sku" disabled class="wh-input bg-slate-50" />
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
                    <label for="unit_cost" class="mb-1 block text-sm font-medium text-slate-700">Unit cost</label>
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
                    <label for="is_perishable" class="text-sm text-slate-700">Perishable</label>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save changes</button>
                <button type="button" class="wh-btn-secondary" @click="toggleActive">
                    {{ item.is_active === false ? 'Activate' : 'Deactivate' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
