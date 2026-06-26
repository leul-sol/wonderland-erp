<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ tab: 'open' }) },
    folios: { type: Array, default: () => [] },
    diningTables: { type: Array, default: () => [] },
    customerTypes: { type: Array, default: () => [] },
});

const showCreateModal = ref(false);

const form = useForm({
    customer_type: 'hotel_guest',
    folio_id: props.folios[0]?.id ?? '',
    customer_ref_id: '',
    dining_table_id: '',
});

const isHotelGuest = computed(() => form.customer_type === 'hotel_guest');
const isEvent = computed(() => form.customer_type === 'event');
const showTablePicker = computed(() => ['outside_cash', 'outside_credit', 'event'].includes(form.customer_type));
const selectedType = computed(() => props.customerTypes.find((type) => type.value === form.customer_type));

watch(
    () => form.customer_type,
    (type) => {
        if (type === 'hotel_guest' && !form.folio_id && props.folios[0]) {
            form.folio_id = props.folios[0].id;
        }
    },
);

const columns = [
    { key: 'order_number', label: 'Order #' },
    { key: 'customer_type', label: 'Customer' },
    { key: 'dining_table', label: 'Table' },
    { key: 'total_amount', label: 'Total', class: 'text-right' },
    { key: 'status', label: 'Status' },
    { key: 'bill_status', label: 'Bill' },
];

const tabs = [
    { value: 'open', label: 'Open' },
    { value: 'finalized', label: 'Awaiting payment' },
    { value: 'billed', label: 'Paid / posted' },
    { value: 'all', label: 'All' },
];

function applyTab(tab) {
    router.get('/fb/orders', tab === 'open' ? { tab } : { tab }, { preserveState: true, replace: true });
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '—';
}

function customerLabel(type) {
    return ({
        hotel_guest: 'Hotel guest',
        outside_cash: 'Walk-in cash',
        outside_credit: 'Walk-in credit',
        event: 'Event',
        employee: 'Staff meal',
    })[type] ?? type;
}

function openCreateModal() {
    form.reset();
    form.customer_type = 'hotel_guest';
    form.folio_id = props.folios[0]?.id ?? '';
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/fb/orders', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}
</script>

<template>
    <AppLayout title="F&B orders">
        <PageHeader title="Restaurant orders" subtitle="Open tabs, finalize bills, and post to folios">
            <template #actions>
                <Link href="/fb/menu" class="wh-btn-secondary">Menu</Link>
                <button type="button" class="wh-btn-primary" @click="openCreateModal">New order</button>
            </template>
        </PageHeader>

        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset transition"
                :class="
                    filters.tab === tab.value
                        ? 'bg-teal-700 text-white ring-teal-700'
                        : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-50'
                "
                @click="applyTab(tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>

        <DataTable list-title="Order queue" :columns="columns" :rows="orders" empty-message="No orders in this queue.">
            <template #cell-order_number="{ row }">
                <Link :href="`/fb/orders/${row.id}`" class="wh-table-link">{{ row.order_number }}</Link>
            </template>
            <template #cell-customer_type="{ row }">
                {{ customerLabel(row.customer_type) }}
            </template>
            <template #cell-dining_table="{ row }">
                {{ row.dining_table?.table_number ?? '—' }}
            </template>
            <template #cell-total_amount="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.total_amount) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status === 'open' ? 'draft' : row.status" />
            </template>
            <template #cell-bill_status="{ row }">
                <StatusBadge v-if="row.bill" :status="row.bill.status" />
                <span v-else class="text-slate-400">—</span>
            </template>
        </DataTable>

        <FormModal
            :open="showCreateModal"
            title="Open restaurant order"
            subtitle="Choose customer type, table, and folio routing"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <label for="customer_type" class="mb-1 block text-sm font-medium text-slate-700">Customer type</label>
                    <select id="customer_type" v-model="form.customer_type" required class="wh-input">
                        <option v-for="type in customerTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                    <p v-if="selectedType" class="mt-1 text-xs text-slate-500">{{ selectedType.description }}</p>
                </div>

                <div v-if="isHotelGuest">
                    <label for="folio_id" class="mb-1 block text-sm font-medium text-slate-700">Guest folio</label>
                    <select id="folio_id" v-model="form.folio_id" required class="wh-input">
                        <option value="" disabled>Select open folio</option>
                        <option v-for="folio in folios" :key="folio.id" :value="folio.id">
                            Folio #{{ folio.id }} · reservation {{ folio.reservation_id }} · balance ETB
                            {{ formatMoney(folio.balance) }}
                        </option>
                    </select>
                    <p v-if="folios.length === 0" class="mt-2 text-sm text-amber-800">
                        No open folios.
                        <Link href="/front-desk/check-in" class="wh-table-link">Check in a guest</Link>
                        first.
                    </p>
                </div>

                <div v-if="isEvent">
                    <label for="customer_ref_id" class="mb-1 block text-sm font-medium text-slate-700">Event reference ID</label>
                    <input id="customer_ref_id" v-model="form.customer_ref_id" type="number" min="1" required class="wh-input" />
                </div>

                <div v-if="showTablePicker">
                    <label for="dining_table_id" class="mb-1 block text-sm font-medium text-slate-700">Dining table (optional)</label>
                    <select id="dining_table_id" v-model="form.dining_table_id" class="wh-input">
                        <option value="">No table / takeaway</option>
                        <option v-for="table in diningTables" :key="table.id" :value="table.id">
                            {{ table.table_number }}{{ table.location ? ` · ${table.location}` : '' }}
                        </option>
                    </select>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button
                        type="button"
                        class="wh-btn-primary"
                        :disabled="form.processing || (isHotelGuest && folios.length === 0)"
                        @click="submitCreate"
                    >
                        Open order
                    </button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
