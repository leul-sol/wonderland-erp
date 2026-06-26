<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    guest: { type: Object, default: null },
});

const isEdit = computed(() => props.guest !== null && props.guest.id);

const form = useForm({
    full_name: props.guest?.full_name ?? '',
    phone: props.guest?.phone ?? '',
    email: props.guest?.email ?? '',
    id_document_type: props.guest?.id_document_type ?? '',
    id_document_number: props.guest?.id_document_number ?? '',
    nationality: props.guest?.nationality ?? '',
    address: props.guest?.address ?? '',
});

function submit() {
    if (isEdit.value) {
        form.put(`/front-desk/guests/${props.guest.id}`);
    } else {
        form.post('/front-desk/guests');
    }
}
</script>

<template>
    <AppLayout :title="isEdit ? 'Edit guest' : 'New guest'">
        <PageHeader
            :title="isEdit ? guest.full_name : 'New guest profile'"
            :subtitle="isEdit ? 'Update guest details for reservations and invoices' : 'Register a guest before check-in'"
        >
            <template #actions>
                <Link href="/front-desk/guests" class="wh-btn-secondary">All guests</Link>
                <Link
                    v-if="isEdit"
                    :href="`/front-desk/check-in?guest_id=${guest.id}`"
                    class="wh-btn-primary"
                >
                    Check in
                </Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="full_name" class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                    <input id="full_name" v-model="form.full_name" type="text" required class="wh-input" />
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
                    <label for="id_document_type" class="mb-1 block text-sm font-medium text-slate-700">ID type</label>
                    <input id="id_document_type" v-model="form.id_document_type" type="text" class="wh-input" placeholder="Passport, National ID" />
                </div>
                <div>
                    <label for="id_document_number" class="mb-1 block text-sm font-medium text-slate-700">ID number</label>
                    <input id="id_document_number" v-model="form.id_document_number" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="nationality" class="mb-1 block text-sm font-medium text-slate-700">Nationality</label>
                    <input id="nationality" v-model="form.nationality" type="text" class="wh-input" />
                </div>
                <div class="sm:col-span-2">
                    <label for="address" class="mb-1 block text-sm font-medium text-slate-700">Address</label>
                    <textarea id="address" v-model="form.address" rows="2" class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Link href="/front-desk/guests" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">
                    {{ isEdit ? 'Save changes' : 'Create guest' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
