<script setup>
import GuestLayout from '../../Layouts/GuestLayout.vue';
import PasswordField from '../../Components/PasswordField.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const form = useForm({
    username: '',
    password: '',
});

const flashError = computed(() => page.props.flash?.error ?? null);
const requestError = ref(null);

const errorMessage = computed(() => form.errors.login || flashError.value || requestError.value);

function submit() {
    requestError.value = null;

    form.post('/login', {
        preserveScroll: true,
        onFinish: () => form.reset('password'),
        onError: () => {
            if (!form.errors.login && !flashError.value) {
                requestError.value = 'Sign in failed. Check your username and password, then try again.';
            }
        },
        onSuccess: () => {
            requestError.value = null;
        },
    });
}
</script>

<template>
    <GuestLayout title="Staff sign in" subtitle="Use your hotel operations account to continue.">
        <div
            v-if="errorMessage"
            class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            role="alert"
        >
            {{ errorMessage }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Username <span class="text-red-600">*</span>
                </label>
                <input
                    id="username"
                    v-model="form.username"
                    type="text"
                    autocomplete="username"
                    class="wh-input"
                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-100': !!errorMessage }"
                    required
                />
                <p v-if="form.errors.username" class="mt-1 text-sm text-red-600">{{ form.errors.username }}</p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Password <span class="text-red-600">*</span>
                </label>
                <PasswordField
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                    :invalid="!!errorMessage"
                    required
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <button type="submit" class="wh-btn-primary w-full" :disabled="form.processing">
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>
        </form>
    </GuestLayout>
</template>
