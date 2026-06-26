<script setup>
import { useForm } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    roomTypes: { type: Array, default: () => [] },
});

const createForm = useForm({
    name: '',
    code: '',
    base_rate: '',
    max_occupancy: 2,
});

const columns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Room type' },
    { key: 'base_rate', label: 'Base rate', class: 'text-right' },
    { key: 'max_occupancy', label: 'Max guests', class: 'text-right' },
    { key: 'is_active', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function submitCreate() {
    createForm.post('/front-desk/settings/room-types', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

async function toggleActive(roomType) {
    const deactivating = roomType.is_active !== false;
    const ok = await confirmAction({
        title: deactivating ? 'Deactivate room type' : 'Activate room type',
        message: deactivating
            ? `Hide "${roomType.name}" from new reservations?`
            : `Restore "${roomType.name}" for bookings?`,
        confirmLabel: deactivating ? 'Deactivate' : 'Activate',
    });

    if (!ok) {
        return;
    }

    useForm({ is_active: !deactivating }).put(`/front-desk/settings/room-types/${roomType.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Hotel settings">
        <PageHeader title="Hotel settings" subtitle="Room types, physical rooms, and rack rates">
            <template #actions>
                <Link href="/front-desk/settings/rooms" class="wh-btn-secondary">Physical rooms</Link>
            </template>
        </PageHeader>

        <form class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add room type</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input v-model="createForm.code" type="text" required class="wh-input" placeholder="Code (STD)" />
                <input v-model="createForm.name" type="text" required class="wh-input" placeholder="Name" />
                <MoneyField v-model="createForm.base_rate" placeholder="Base rate" required />
                <input v-model.number="createForm.max_occupancy" type="number" min="1" required class="wh-input" placeholder="Max guests" />
                <button type="submit" class="wh-btn-primary" :disabled="createForm.processing">Add</button>
            </div>
        </form>

        <DataTable list-title="Room types" :columns="columns" :rows="roomTypes" empty-message="No room types configured.">
            <template #cell-base_rate="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.base_rate) }}</span>
            </template>
            <template #cell-is_active="{ row }">
                <StatusBadge :status="row.is_active === false ? 'inactive' : 'active'" />
            </template>
            <template #cell-actions="{ row }">
                <button type="button" class="wh-btn-secondary text-xs" @click="toggleActive(row)">
                    {{ row.is_active === false ? 'Activate' : 'Deactivate' }}
                </button>
            </template>
        </DataTable>
    </AppLayout>
</template>
