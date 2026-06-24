<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    defaultHireDate: { type: String, required: true },
});

const form = useForm({
    full_name: '',
    email: '',
    department_id: props.departments[0]?.id ?? '',
    position_id: props.positions[0]?.id ?? '',
    job_title: '',
    base_salary: '',
    pension_category: 'covered',
    default_role: 'report_viewer',
    hire_date: props.defaultHireDate,
});

function submit() {
    form.post('/hr/employees');
}
</script>

<template>
    <AppLayout title="Add employee">
        <PageHeader title="Add employee" subtitle="Creates workforce record; S1 user is provisioned asynchronously">
            <template #actions>
                <Link href="/hr/employees" class="wh-btn-secondary">Back to list</Link>
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
                    <label for="hire_date" class="mb-1 block text-sm font-medium text-slate-700">Hire date</label>
                    <input id="hire_date" v-model="form.hire_date" type="date" class="wh-input" />
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
                    <label for="job_title" class="mb-1 block text-sm font-medium text-slate-700">Job title</label>
                    <input id="job_title" v-model="form.job_title" type="text" class="wh-input" />
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
            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create employee</button>
            </div>
        </form>
    </AppLayout>
</template>
