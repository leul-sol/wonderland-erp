<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';
import { usePortalPermission } from '../../../composables/usePortalPermission';

const props = defineProps({
    pageLoad: { type: Object, default: null },
});

const menuItems = computed(() => props.pageLoad?.menuItems ?? []);
const categories = computed(() => props.pageLoad?.categories ?? []);

const showCreateModal = ref(false);

const { canManageMenuCatalog } = usePortalPermission();

const form = useForm({
    code: '',
    name: '',
    price: '',
    employee_price: '',
    category_id: '',
});

const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Item' },
    { key: 'category', label: 'Category' },
    { key: 'price', label: 'Guest price', class: 'text-right' },
    { key: 'employee_price', label: 'Staff price', class: 'text-right' },
    { key: 'has_recipe', label: 'Recipe' },
    { key: 'is_active', label: 'Available' },
];

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = Number.parseFloat(value);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function openCreateModal() {
    form.reset();
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/fb/menu-items', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, {
    when: () => canManageMenuCatalog(),
    onOpen: openCreateModal,
});
</script>

<template>
    <AppLayout title="Menu items">
        <PageHeader title="Menu items" subtitle="Catalog admin — prices, availability, and recipes">
            <template #actions>
                <Link v-if="canManageMenuCatalog()" href="/fb/settings" class="wh-btn-secondary">Catalog admin</Link>
                <button v-if="canManageMenuCatalog()" type="button" class="wh-btn-primary" @click="openCreateModal">New item</button>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <DataTable list-title="All menu items" :columns="columns" :rows="menuItems" empty-message="No menu items found.">
            <template #empty>
                <p>No menu items found.</p>
                <button v-if="canManageMenuCatalog()" type="button" class="wh-btn-primary mt-3" @click="openCreateModal">
                    Add your first menu item
                </button>
            </template>
            <template #cell-code="{ row }">
                <Link v-if="canManageMenuCatalog()" :href="`/fb/menu-items/${row.id}/edit`" class="wh-table-link">{{ row.code }}</Link>
                <span v-else>{{ row.code }}</span>
            </template>
            <template #cell-name="{ row }">
                <Link v-if="canManageMenuCatalog()" :href="`/fb/menu-items/${row.id}/edit`" class="wh-table-link">{{ row.name }}</Link>
                <span v-else>{{ row.name }}</span>
            </template>
            <template #cell-category="{ row }">
                {{ row.category ?? '—' }}
            </template>
            <template #cell-price="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.price) }}</span>
            </template>
            <template #cell-employee_price="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.employee_price) }}</span>
            </template>
            <template #cell-has_recipe="{ row }">
                {{ row.has_recipe ? 'Yes' : '—' }}
            </template>
            <template #cell-is_active="{ row }">
                <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canManageMenuCatalog()"
            :open="showCreateModal"
            title="New menu item"
            subtitle="Add a sellable item to the restaurant catalog"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
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
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Create item</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
