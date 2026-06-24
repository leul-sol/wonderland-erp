<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    folios: { type: Array, default: () => [] },
    selectedFolioId: { type: Number, default: null },
});

const form = useForm({
    folio_id: props.selectedFolioId ?? props.folios[0]?.id ?? '',
});

function submit() {
    form.post('/fb/orders');
}

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout title="Post F&B to folio">
        <PageHeader
            title="Post F&B to folio"
            subtitle="Select an open guest folio, then add menu items and finalize"
        />

        <form class="wh-card mx-auto max-w-xl p-6" @submit.prevent="submit">
            <div>
                <label for="folio_id" class="mb-1 block text-sm font-medium text-slate-700">Guest folio</label>
                <select id="folio_id" v-model="form.folio_id" required class="wh-input">
                    <option value="" disabled>Select open folio</option>
                    <option v-for="folio in folios" :key="folio.id" :value="folio.id">
                        Folio #{{ folio.id }} · reservation {{ folio.reservation_id }} · balance ETB
                        {{ formatMoney(folio.balance) }}
                    </option>
                </select>
                <p v-if="folios.length === 0" class="mt-2 text-sm text-amber-800">
                    No open folios. Check in a guest first.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Link href="/fb/menu" class="wh-btn-secondary">View menu</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing || folios.length === 0">
                    Open F&B order
                </button>
            </div>
        </form>
    </AppLayout>
</template>
