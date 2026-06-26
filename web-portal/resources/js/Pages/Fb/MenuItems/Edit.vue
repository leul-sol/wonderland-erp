<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    menuItem: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    inventoryItems: { type: Array, default: () => [] },
});

const itemForm = useForm({
    name: props.menuItem.name ?? '',
    price: props.menuItem.price ?? '',
    employee_price: props.menuItem.employee_price ?? '',
    category_id: props.menuItem.category_id ?? '',
    is_available: props.menuItem.is_active !== false,
});

const recipeForm = useForm({
    ingredients: (props.menuItem.ingredients ?? []).map((line) => ({
        inventory_item_id: line.inventory_item_id,
        quantity: line.quantity,
    })),
});

const isAvailable = computed({
    get: () => itemForm.is_available,
    set: (value) => {
        itemForm.is_available = value;
    },
});

function submitItem() {
    itemForm.put(`/fb/menu-items/${props.menuItem.id}`, { preserveScroll: true });
}

async function toggleAvailability() {
    const next = !isAvailable.value;
    const ok = await confirmAction({
        title: next ? 'Make item available' : 'Mark item unavailable',
        message: next
            ? `"${props.menuItem.name}" will appear on the floor menu and POS.`
            : `"${props.menuItem.name}" will be hidden from new orders.`,
        confirmLabel: next ? 'Make available' : 'Mark unavailable',
    });

    if (!ok) {
        return;
    }

    isAvailable.value = next;
    submitItem();
}

function addIngredientRow() {
    recipeForm.ingredients.push({ inventory_item_id: '', quantity: 1 });
}

function removeIngredientRow(index) {
    recipeForm.ingredients.splice(index, 1);
}

function submitRecipe() {
    recipeForm.put(`/fb/menu-items/${props.menuItem.id}/recipe`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="menuItem.name">
        <PageHeader :title="menuItem.name" :subtitle="`${menuItem.code} · catalog item`">
            <template #actions>
                <StatusBadge :status="menuItem.is_active ? 'active' : 'inactive'" />
                <button type="button" class="wh-btn-secondary text-xs" @click="toggleAvailability">
                    {{ menuItem.is_active ? 'Mark unavailable' : 'Make available' }}
                </button>
                <Link href="/fb/menu-items" class="wh-btn-secondary text-xs">All items</Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 xl:grid-cols-2">
            <form class="wh-card p-4" @submit.prevent="submitItem">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Item details</h3>
                <div class="grid gap-4">
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                        <input id="name" v-model="itemForm.name" type="text" required class="wh-input" />
                    </div>
                    <div>
                        <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                        <select id="category_id" v-model="itemForm.category_id" class="wh-input">
                            <option value="">Uncategorized</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="price" class="mb-1 block text-sm font-medium text-slate-700">Guest price</label>
                            <MoneyField id="price" v-model="itemForm.price" required />
                        </div>
                        <div>
                            <label for="employee_price" class="mb-1 block text-sm font-medium text-slate-700">Staff price</label>
                            <MoneyField id="employee_price" v-model="itemForm.employee_price" />
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="wh-btn-primary" :disabled="itemForm.processing">Save details</button>
                </div>
            </form>

            <form class="wh-card p-4" @submit.prevent="submitRecipe">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Recipe (per serving)</h3>
                    <button type="button" class="wh-btn-secondary text-xs" @click="addIngredientRow">Add line</button>
                </div>
                <p class="mb-4 text-sm text-slate-600">
                    Inventory is deducted when orders with recipes are finalized.
                </p>
                <div v-if="recipeForm.ingredients.length === 0" class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">
                    No ingredients yet. Add inventory lines to enable stock deduction.
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="(line, index) in recipeForm.ingredients"
                        :key="index"
                        class="grid gap-2 rounded-lg border border-slate-200 p-3 sm:grid-cols-[1fr_120px_auto]"
                    >
                        <select v-model="line.inventory_item_id" required class="wh-input">
                            <option value="" disabled>Select ingredient</option>
                            <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                                {{ item.sku }} · {{ item.name }}
                            </option>
                        </select>
                        <input
                            v-model.number="line.quantity"
                            type="number"
                            min="0.001"
                            step="0.001"
                            required
                            class="wh-input"
                            placeholder="Qty"
                        />
                        <button type="button" class="wh-btn-secondary text-xs" @click="removeIngredientRow(index)">
                            Remove
                        </button>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button
                        type="submit"
                        class="wh-btn-primary"
                        :disabled="recipeForm.processing || recipeForm.ingredients.length === 0"
                    >
                        Save recipe
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
