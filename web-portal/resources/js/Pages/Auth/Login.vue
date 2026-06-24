<script setup>
import GuestLayout from '../../Layouts/GuestLayout.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    username: '',
    password: '',
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout title="Staff sign in">
        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-slate-700">Username</label>
                <input
                    id="username"
                    v-model="form.username"
                    type="text"
                    autocomplete="username"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100"
                    required
                />
                <p v-if="form.errors.username" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</p>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-100"
                    required
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800 disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>
        </form>
    </GuestLayout>
</template>
