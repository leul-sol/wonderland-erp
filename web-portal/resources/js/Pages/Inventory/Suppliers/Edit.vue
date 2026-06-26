<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    supplier: { type: Object, required: true },
});

const form = useForm({
    name: props.supplier.name ?? '',
    contact_name: props.supplier.contact_name ?? '',
    phone: props.supplier.phone ?? '',
    email: props.supplier.email ?? '',
    address: props.supplier.address ?? '',
    payment_terms: props.supplier.payment_terms ?? '',
    is_active: props.supplier.is_active !== false,
});

function submit() {
    form.put(`/inventory/suppliers/${props.supplier.id}`);
}

async function toggleActive() {
    const deactivating = form.is_active;
    const ok = await confirmAction({
        title: deactivating ? 'Deactivate supplier' : 'Activate supplier',
        message: deactivating ? `Deactivate ${props.supplier.name}?` : `Restore ${props.supplier.name}?`,
        confirmLabel: deactivating ? 'Deactivate' : 'Activate',
    });

    if (!ok) {
        return;
    }

    form.is_active = !deactivating;
    form.put(`/inventory/suppliers/${props.supplier.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="`Edit ${supplier.name}`">
        <PageHeader :title="`Edit ${supplier.name}`" subtitle="Supplier master data">
            <template #actions>
                <StatusBadge :status="supplier.is_active === false ? 'inactive' : 'active'" />
                <Link :href="`/inventory/suppliers/${supplier.id}`" class="wh-btn-secondary text-xs">View balance</Link>
            </template>
        </PageHeader>

        <form class="wh-card max-w-2xl space-y-4 p-6" @submit.prevent="submit">
            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Supplier name</label>
                <input id="name" v-model="form.name" type="text" required class="wh-input" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="contact_name" class="mb-1 block text-sm font-medium text-slate-700">Contact</label>
                    <input id="contact_name" v-model="form.contact_name" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
                    <input id="phone" v-model="form.phone" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" v-model="form.email" type="email" class="wh-input" />
                </div>
                <div>
                    <label for="payment_terms" class="mb-1 block text-sm font-medium text-slate-700">Payment terms</label>
                    <input id="payment_terms" v-model="form.payment_terms" type="text" class="wh-input" />
                </div>
            </div>
            <div>
                <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                <textarea id="address" v-model="form.address" rows="2" class="wh-input" />
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save</button>
                <button type="button" class="wh-btn-secondary" @click="toggleActive">
                    {{ supplier.is_active === false ? 'Activate' : 'Deactivate' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
