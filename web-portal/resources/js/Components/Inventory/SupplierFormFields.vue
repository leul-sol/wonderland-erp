<script setup>
import FormLabel from '../FormLabel.vue';

defineProps({
    form: { type: Object, required: true },
    showActiveToggle: { type: Boolean, default: false },
});
</script>

<template>
    <div class="space-y-4">
        <div>
            <FormLabel for="supplier_name" required>Supplier name</FormLabel>
            <input id="supplier_name" v-model="form.name" type="text" required class="wh-input" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <FormLabel for="supplier_contact_name">Contact</FormLabel>
                <input id="supplier_contact_name" v-model="form.contact_name" type="text" class="wh-input" />
            </div>
            <div>
                <FormLabel for="supplier_phone">Phone</FormLabel>
                <input id="supplier_phone" v-model="form.phone" type="text" class="wh-input" />
            </div>
            <div>
                <FormLabel for="supplier_email">Email</FormLabel>
                <input id="supplier_email" v-model="form.email" type="email" class="wh-input" />
            </div>
            <div>
                <FormLabel for="supplier_payment_terms">Payment terms</FormLabel>
                <input id="supplier_payment_terms" v-model="form.payment_terms" type="text" class="wh-input" placeholder="Net 30" />
            </div>
        </div>
        <div>
            <FormLabel for="supplier_address">Address</FormLabel>
            <textarea id="supplier_address" v-model="form.address" rows="2" class="wh-input" />
        </div>
        <div v-if="showActiveToggle" class="flex items-center gap-2">
            <input id="supplier_is_active" v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
            <label for="supplier_is_active" class="text-sm text-slate-700">Active supplier</label>
        </div>
    </div>
</template>
