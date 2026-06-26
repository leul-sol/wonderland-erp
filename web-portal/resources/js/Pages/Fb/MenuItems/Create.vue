<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    code: '',
    name: '',
    price: '',
    employee_price: '',
    category_id: '',
});

function submit() {
    form.post('/fb/menu-items');
}
</script>

<template>
    <AppLayout title="New menu item">
        <PageHeader title="New menu item" subtitle="Add a sellable item to the restaurant catalog">
            <template #actions>
                <Link href="/fb/menu-items" class="wh-btn-secondary">All items</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Item code</label>
                    <input id="code" v-model="form.code" type="text" required class="wh-input" placeholder="BURGER-CL" />
                </div>
                <div>
                    <label for="category_id" class="mb-1 block text-sm font-medium text-slate-700">Category</label>
                    <select id="category_id" v-model="form.category_id" class="wh-input">
                        <option value="">Uncategorized</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="name" v-model="form.name" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="price" class="mb-1 block text-sm font-medium text-slate-700">Guest price (ETB)</label>
                    <MoneyField id="price" v-model="form.price" required />
                </div>
                <div>
                    <label for="employee_price" class="mb-1 block text-sm font-medium text-slate-700">Staff meal price (ETB)</label>
                    <MoneyField id="employee_price" v-model="form.employee_price" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link href="/fb/menu-items" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create item</button>
            </div>
        </form>
    </AppLayout>
</template>
