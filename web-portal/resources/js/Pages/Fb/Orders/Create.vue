<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    folios: { type: Array, default: () => [] },
    diningTables: { type: Array, default: () => [] },
    selectedFolioId: { type: Number, default: null },
    customerTypes: { type: Array, default: () => [] },
});

const form = useForm({
    customer_type: 'hotel_guest',
    folio_id: props.selectedFolioId ?? props.folios[0]?.id ?? '',
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

function submit() {
    form.post('/fb/orders');
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="New F&B order">
        <PageHeader title="Open restaurant order" subtitle="Choose customer type, table, and folio routing">
            <template #actions>
                <Link href="/fb/orders" class="wh-btn-secondary">Order queue</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4">
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
                        <Link href="/front-desk/rooms?open=check-in" class="wh-table-link">Check in a guest</Link>
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
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link href="/fb/orders" class="wh-btn-secondary">Cancel</Link>
                <button
                    type="submit"
                    class="wh-btn-primary"
                    :disabled="form.processing || (isHotelGuest && folios.length === 0)"
                >
                    Open order
                </button>
            </div>
        </form>
    </AppLayout>
</template>
