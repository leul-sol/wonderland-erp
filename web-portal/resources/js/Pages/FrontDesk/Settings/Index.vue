<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormLabel from '../../../Components/FormLabel.vue';
import MoneyField from '../../../Components/MoneyField.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
});

const roomTypes = computed(() => props.pageLoad?.roomTypes ?? []);

const { canManageHotelSettings } = usePortalPermission();

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
                <Link v-if="canManageHotelSettings()" href="/front-desk/settings/rooms" class="wh-btn-secondary">
                    Physical rooms
                </Link>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <form v-if="canManageHotelSettings()" class="wh-card mb-6 p-4" @submit.prevent="submitCreate">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Add room type</h3>
            <div class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <FormLabel for="room_type_code" required compact>Code</FormLabel>
                    <input id="room_type_code" v-model="createForm.code" type="text" required class="wh-input" placeholder="STD" />
                </div>
                <div>
                    <FormLabel for="room_type_name" required compact>Name</FormLabel>
                    <input id="room_type_name" v-model="createForm.name" type="text" required class="wh-input" placeholder="Standard Room" />
                </div>
                <div>
                    <FormLabel for="room_type_base_rate" required compact>Base rate</FormLabel>
                    <MoneyField id="room_type_base_rate" v-model="createForm.base_rate" hide-label required />
                </div>
                <div>
                    <FormLabel for="room_type_max_occupancy" required compact>Max guests</FormLabel>
                    <input id="room_type_max_occupancy" v-model.number="createForm.max_occupancy" type="number" min="1" required class="wh-input" />
                </div>
                <div>
                    <button type="submit" class="wh-btn-primary w-full lg:w-auto" :disabled="createForm.processing">Add</button>
                </div>
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
                <button
                    v-if="canManageHotelSettings()"
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
