<script setup>
import GuestLayout from '../../Layouts/GuestLayout.vue';
import PasswordField from '../../Components/PasswordField.vue';
import { Link, useForm } from '@inertiajs/vue3';

defineProps({
    required: { type: Boolean, default: true },
    username: { type: String, default: '' },
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/account/change-password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function fieldInvalid(key) {
    return Boolean(form.errors[key]);
}
</script>

<template>
    <GuestLayout
        :title="required ? 'Change your password' : 'Update password'"
        :subtitle="required ? 'Set a new password before you can access the portal.' : 'Keep your account secure with a strong password.'"
    >
        <div
            v-if="required"
            class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        >
            You must set a new password before continuing.
        </div>

        <p v-if="username" class="mb-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Signed in as <span class="font-semibold text-slate-900">{{ username }}</span>
        </p>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <label for="current_password" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Current password <span class="text-red-600">*</span>
                </label>
                <PasswordField
                    id="current_password"
                    v-model="form.current_password"
                    autocomplete="current-password"
                    :invalid="fieldInvalid('current_password')"
                    required
                />
                <p v-if="form.errors.current_password" class="mt-1 text-sm text-red-600">
                    {{ form.errors.current_password }}
                </p>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">
                    New password <span class="text-red-600">*</span>
                </label>
                <PasswordField
                    id="password"
                    v-model="form.password"
                    autocomplete="new-password"
                    :invalid="fieldInvalid('password')"
                    required
                    :minlength="10"
                />
                <p class="mt-1 text-xs text-slate-500">
                    At least 10 characters with uppercase, a number, and a symbol.
                </p>
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Confirm new password <span class="text-red-600">*</span>
                </label>
                <PasswordField
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                    :invalid="fieldInvalid('password_confirmation')"
                    required
                    :minlength="10"
                />
                <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>

            <button type="submit" class="wh-btn-primary w-full" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : 'Save password' }}
            </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-6 text-center">
            <Link
                href="/logout"
                method="post"
                as="button"
                class="text-sm font-medium text-slate-500 hover:text-teal-700"
            >
                Sign out instead
            </Link>
        </div>
    </GuestLayout>
</template>
