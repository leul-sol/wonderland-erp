<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const form = useForm({
    username: '',
    email: '',
    password: '',
    display_name: '',
    employee_id: '',
});

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <AppLayout title="Create user">
        <PageHeader title="Create platform user" subtitle="User must change password on first login">
            <template #actions>
                <Link href="/admin/users" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-lg p-6" @submit.prevent="submit">
            <div class="grid gap-4">
                <div>
                    <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                    <input id="username" v-model="form.username" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" v-model="form.email" type="email" required class="wh-input" />
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Temporary password</label>
                    <input id="password" v-model="form.password" type="password" required minlength="10" class="wh-input" />
                </div>
                <div>
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input id="display_name" v-model="form.display_name" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="employee_id" class="mb-1 block text-sm font-medium text-slate-700">Employee ID (optional)</label>
                    <input id="employee_id" v-model="form.employee_id" type="number" min="1" class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create user</button>
            </div>
        </form>
    </AppLayout>
</template>
