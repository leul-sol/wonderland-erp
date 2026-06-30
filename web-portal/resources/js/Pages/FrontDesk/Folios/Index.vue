<script setup>
import { Link } from '@inertiajs/vue3';
import CheckInModal from '../../../Components/FrontDesk/CheckInModal.vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useCheckInModal } from '../../../composables/useCheckInModal';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    folios: { type: Array, default: () => [] },
    checkInLoad: { type: Object, default: null },
    checkInGuestId: { type: Number, default: null },
});

const { showCheckInModal, checkInGuestId, openCheckInModal, closeCheckInModal, canCheckInGuest } = useCheckInModal(
    props.checkInGuestId,
);

const columns = [
    { key: 'id', label: 'Folio #' },
    { key: 'reservation_id', label: 'Reservation' },
    { key: 'status', label: 'Status' },
    { key: 'balance', label: 'Balance', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Open folios">
        <PageHeader title="Open folios" subtitle="Select a folio to post charges, settle, and check out">
            <template #actions>
                <button v-if="canCheckInGuest()" type="button" class="wh-btn-primary" @click="openCheckInModal()">
                    Check in guest
                </button>
            </template>
        </PageHeader>

        <PageDataSection keys="folios">
        <DataTable list-title="Folio list" selectable :columns="columns" :rows="folios" empty-message="No open folios.">
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-balance="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.balance) }}</span>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/front-desk/folios/${row.id}`" class="wh-btn-secondary text-xs">Open folio</Link>
            </template>
        </DataTable>
        </PageDataSection>

        <CheckInModal
            v-if="canCheckInGuest()"
            :open="showCheckInModal"
            :page-load="checkInLoad"
            :initial-guest-id="checkInGuestId"
            @close="closeCheckInModal"
        />
    </AppLayout>
</template>
