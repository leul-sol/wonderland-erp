<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import GuestFormFields from '../../../Components/FrontDesk/GuestFormFields.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { usePortalPermission } from '../../../composables/usePortalPermission';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    guest: { type: Object, default: null },
});

const isEdit = computed(() => props.guest !== null && props.guest.id);

const { canCheckInGuest } = usePortalPermission();

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
                    v-if="isEdit && canCheckInGuest()"
                    :href="`/front-desk/guests?open=check-in&guest_id=${guest.id}`"
                    class="wh-btn-primary"
                >
                    Check in
                </Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <GuestFormFields :form="form" />
            <div class="mt-6 flex justify-end gap-3">
                <Link href="/front-desk/guests" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">
                    {{ isEdit ? 'Save changes' : 'Create guest' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>
