<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const form = useForm({
    name: '',
    display_name: '',
    description: '',
});

function submit() {
    form.post('/admin/roles');
}
</script>

<template>
    <AppLayout title="Create role">
        <PageHeader title="Create custom role" subtitle="System roles are seeded; add roles for special access bundles">
            <template #actions>
                <Link href="/admin/roles" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-lg p-6" @submit.prevent="submit">
            <div class="grid gap-4">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Role slug</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        pattern="[A-Za-z0-9_-]+"
                        class="wh-input"
                        placeholder="e.g. night_auditor"
                    />
                    <p class="mt-1 text-xs text-slate-500">Letters, numbers, underscores, and hyphens only.</p>
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input id="display_name" v-model="form.display_name" type="text" required class="wh-input" />
                    <p v-if="form.errors.display_name" class="mt-1 text-sm text-red-600">{{ form.errors.display_name }}</p>
                </div>
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" v-model="form.description" rows="3" class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create role</button>
            </div>
        </form>
    </AppLayout>
</template>
