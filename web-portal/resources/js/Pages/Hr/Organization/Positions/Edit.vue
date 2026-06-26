<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../../Components/PageHeader.vue';
import AppLayout from '../../../../Layouts/AppLayout.vue';

const props = defineProps({
    position: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
});

const form = useForm({
    title: props.position.title ?? '',
    department_id: props.position.department_id ?? '',
    grade: props.position.grade ?? '',
    transport_allowance: props.position.transport_allowance ?? '',
    housing_allowance: props.position.housing_allowance ?? '',
});

function submit() {
    form.patch(`/hr/positions/${props.position.id}`);
}
</script>

<template>
    <AppLayout :title="`Edit ${position.title}`">
        <PageHeader :title="`Edit ${position.title}`" :subtitle="position.department?.name ?? 'Position'">
            <template #actions>
                <Link href="/hr/positions" class="wh-btn-secondary">Back to positions</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="title" class="mb-1 block text-sm font-medium text-slate-700">Title</label>
                    <input id="title" v-model="form.title" type="text" required maxlength="80" class="wh-input" />
                </div>
                <div>
                    <label for="department_id" class="mb-1 block text-sm font-medium text-slate-700">Department</label>
                    <select id="department_id" v-model="form.department_id" required class="wh-input">
                        <option v-for="department in departments" :key="department.id" :value="department.id">
                            {{ department.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label for="grade" class="mb-1 block text-sm font-medium text-slate-700">Grade</label>
                    <input id="grade" v-model="form.grade" type="text" maxlength="10" class="wh-input" />
                </div>
                <div>
                    <label for="transport_allowance" class="mb-1 block text-sm font-medium text-slate-700">Transport allowance (ETB)</label>
                    <input id="transport_allowance" v-model="form.transport_allowance" type="number" step="0.01" min="0" class="wh-input" />
                </div>
                <div>
                    <label for="housing_allowance" class="mb-1 block text-sm font-medium text-slate-700">Housing allowance (ETB)</label>
                    <input id="housing_allowance" v-model="form.housing_allowance" type="number" step="0.01" min="0" class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Link href="/hr/positions" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save changes</button>
            </div>
        </form>
    </AppLayout>
</template>
