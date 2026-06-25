<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    user: { type: Object, required: true },
});

const form = useForm({
    email: props.user.email ?? '',
    display_name: props.user.display_name ?? '',
    employee_id: props.user.employee_id ?? '',
    is_active: props.user.is_active ?? true,
});

function submit() {
    form.put(`/admin/users/${props.user.id}`, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Edit user">
        <PageHeader
            :title="user.display_name ?? user.username"
            subtitle="Update account details"
        >
            <template #actions>
                <Link :href="`/admin/users/${user.id}`" class="wh-btn-secondary">Cancel</Link>
            </template>
        </PageHeader>

        <form class="wh-card max-w-2xl p-5" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                    <input type="text" class="wh-input bg-slate-50" :value="user.username" disabled />
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" v-model="form.email" type="email" class="wh-input" required />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input id="display_name" v-model="form.display_name" type="text" class="wh-input" />
                </div>

                <div>
                    <label for="employee_id" class="mb-1 block text-sm font-medium text-slate-700">Employee ID</label>
                    <input id="employee_id" v-model="form.employee_id" type="number" min="1" class="wh-input" />
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
                        Account is active
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <Link :href="`/admin/users/${user.id}`" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">
                    Save changes
                </button>
            </div>
        </form>
    </AppLayout>
</template>
