<script setup>
import FormLabel from '../FormLabel.vue';

defineProps({
    form: { type: Object, required: true },
});
</script>

<template>
    <div class="space-y-4">
        <div>
            <FormLabel for="guest_full_name" required>Full name</FormLabel>
            <input id="guest_full_name" v-model="form.full_name" type="text" required class="wh-input" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <FormLabel for="guest_phone">Phone</FormLabel>
                <input id="guest_phone" v-model="form.phone" type="text" class="wh-input" />
            </div>
            <div>
                <FormLabel for="guest_email">Email</FormLabel>
                <input id="guest_email" v-model="form.email" type="email" class="wh-input" />
            </div>
            <div>
                <FormLabel for="guest_id_document_type">ID type</FormLabel>
                <input id="guest_id_document_type" v-model="form.id_document_type" type="text" class="wh-input" placeholder="Passport, National ID" />
            </div>
            <div>
                <FormLabel for="guest_id_document_number">ID number</FormLabel>
                <input id="guest_id_document_number" v-model="form.id_document_number" type="text" class="wh-input" />
            </div>
            <div class="sm:col-span-2">
                <FormLabel for="guest_nationality">Nationality</FormLabel>
                <input id="guest_nationality" v-model="form.nationality" type="text" class="wh-input" />
            </div>
            <div class="sm:col-span-2">
                <FormLabel for="guest_address">Address</FormLabel>
                <textarea id="guest_address" v-model="form.address" rows="2" class="wh-input" />
            </div>
        </div>
    </div>
</template>
