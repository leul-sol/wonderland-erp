<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    role: { type: Object, required: true },
});

const form = useForm({
    name: props.role.name ?? '',
    display_name: props.role.display_name ?? '',
    description: props.role.description ?? '',
});

function submit() {
    form.put(`/admin/roles/${props.role.id}`);
}
</script>

<template>
    <AppLayout title="Edit role">
        <PageHeader
            :title="role.display_name"
            :subtitle="role.is_system ? 'System role — slug cannot be changed' : 'Custom role'"
        >
            <template #actions>
                <Link :href="`/admin/roles/${role.id}`" class="wh-btn-secondary">Cancel</Link>
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
                        class="wh-input"
                        :class="role.is_system ? 'bg-slate-50' : ''"
                        :disabled="role.is_system"
                        :required="!role.is_system"
                    />
                </div>
                <div>
                    <label for="display_name" class="mb-1 block text-sm font-medium text-slate-700">Display name</label>
                    <input id="display_name" v-model="form.display_name" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <textarea id="description" v-model="form.description" rows="3" class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <Link :href="`/admin/roles/${role.id}`" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save changes</button>
            </div>
        </form>
    </AppLayout>
</template>
