<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    contact_name: '',
    phone: '',
    email: '',
    address: '',
    payment_terms: 'Net 30',
});

function submit() {
    form.post('/inventory/suppliers');
}
</script>

<template>
    <AppLayout title="New supplier">
        <PageHeader title="New supplier" subtitle="Vendor master for purchase orders and payables">
            <template #actions>
                <Link href="/inventory/suppliers" class="wh-btn-secondary">All suppliers</Link>
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
            <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create supplier</button>
        </form>
    </AppLayout>
</template>
