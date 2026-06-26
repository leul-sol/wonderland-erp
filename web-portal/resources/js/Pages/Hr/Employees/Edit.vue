<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    employee: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
});

const form = useForm({
    full_name: props.employee.full_name ?? '',
    email: props.employee.email ?? '',
    department_id: props.employee.department?.id ?? '',
    position_id: props.employee.position?.id ?? '',
    job_title: props.employee.job_title ?? '',
    base_salary: props.employee.base_salary ?? '',
    pension_category: props.employee.pension_category ?? 'covered',
    default_role: props.employee.default_role ?? 'report_viewer',
});

function submit() {
    form.patch(`/hr/employees/${props.employee.id}`);
}
</script>

<template>
    <AppLayout :title="`Edit ${employee.full_name}`">
        <PageHeader :title="`Edit ${employee.full_name}`" :subtitle="employee.employee_number">
            <template #actions>
                <Link :href="`/hr/employees/${employee.id}`" class="wh-btn-secondary">Back to profile</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="full_name" class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                    <input id="full_name" v-model="form.full_name" type="text" required class="wh-input" />
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" v-model="form.email" type="email" class="wh-input" />
                </div>
                <div>
                    <label for="job_title" class="mb-1 block text-sm font-medium text-slate-700">Job title</label>
                    <input id="job_title" v-model="form.job_title" type="text" class="wh-input" />
                </div>
                <div>
                    <label for="department_id" class="mb-1 block text-sm font-medium text-slate-700">Department</label>
                    <select id="department_id" v-model="form.department_id" class="wh-input">
                        <option value="">None</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                    </select>
                </div>
                <div>
                    <label for="position_id" class="mb-1 block text-sm font-medium text-slate-700">Position</label>
                    <select id="position_id" v-model="form.position_id" class="wh-input">
                        <option value="">None</option>
                        <option v-for="position in positions" :key="position.id" :value="position.id">
                            {{ position.title }}
                        </option>
                    </select>
                </div>
                <div>
                    <label for="base_salary" class="mb-1 block text-sm font-medium text-slate-700">Base salary (ETB)</label>
                    <input id="base_salary" v-model="form.base_salary" type="number" step="0.01" min="0" required class="wh-input" />
                </div>
                <div>
                    <label for="pension_category" class="mb-1 block text-sm font-medium text-slate-700">Pension</label>
                    <select id="pension_category" v-model="form.pension_category" class="wh-input">
                        <option value="covered">Covered</option>
                        <option value="not_covered">Not covered</option>
                    </select>
                </div>
                <div>
                    <label for="default_role" class="mb-1 block text-sm font-medium text-slate-700">Default portal role</label>
                    <input id="default_role" v-model="form.default_role" type="text" class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Link :href="`/hr/employees/${employee.id}`" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save changes</button>
            </div>
        </form>
    </AppLayout>
</template>
