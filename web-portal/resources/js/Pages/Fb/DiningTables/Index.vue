<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    tables: { type: Array, default: () => [] },
});

const { canManageMenuCatalog } = usePortalPermission();

const createForm = useForm({
    table_number: '',
    capacity: 4,
    location: '',
});

const columns = [
    { key: 'table_number', label: 'Table' },
    { key: 'capacity', label: 'Seats', class: 'text-right' },
    { key: 'location', label: 'Location' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function submitCreate() {
    createForm.post('/fb/dining-tables', {
        preserveScroll: true,
        onSuccess: () => createForm.reset('table_number', 'location'),
    });
}

async function toggleActive(table) {
    const deactivating = table.is_active !== false;
    const ok = await confirmAction({
        title: deactivating ? 'Deactivate table' : 'Activate table',
        message: deactivating
            ? `Hide table ${table.table_number} from order seating?`
            : `Restore table ${table.table_number} for dine-in orders?`,
        confirmLabel: deactivating ? 'Deactivate' : 'Activate',
    });

    if (!ok) {
        return;
    }

    useForm({ is_active: !deactivating }).put(`/fb/dining-tables/${table.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Dining tables">
        <PageHeader title="Dining tables" subtitle="Floor layout for dine-in and event seating">
            <template #actions>
                <Link v-if="canManageMenuCatalog()" href="/fb/settings" class="wh-btn-secondary">Catalog admin</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="tables">
        <form v-if="canManageMenuCatalog()" class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add table</h3>
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-28">
                    <label for="table_number" class="mb-1 block text-xs font-medium text-slate-600">Table #</label>
                    <input id="table_number" v-model="createForm.table_number" type="text" required class="wh-input" />
                </div>
                <div class="w-24">
                    <label for="capacity" class="mb-1 block text-xs font-medium text-slate-600">Seats</label>
                    <input id="capacity" v-model.number="createForm.capacity" type="number" min="1" required class="wh-input" />
                </div>
                <div class="min-w-[180px] flex-1">
                    <label for="location" class="mb-1 block text-xs font-medium text-slate-600">Location</label>
                    <input id="location" v-model="createForm.location" type="text" class="wh-input" placeholder="Terrace, Main hall" />
                </div>
                <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Add</button>
            </div>
        </form>

        <DataTable list-title="Tables" :columns="columns" :rows="tables" empty-message="No dining tables configured.">
            <template #cell-is_active="{ row }">
                <StatusBadge :status="row.is_active === false ? 'inactive' : 'active'" />
            </template>
            <template #cell-actions="{ row }">
                <button
                    v-if="canManageMenuCatalog()"
                    type="button"
                    class="wh-btn-secondary text-xs"
                    @click="toggleActive(row)"
                >
                    {{ row.is_active === false ? 'Activate' : 'Deactivate' }}
                </button>
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
