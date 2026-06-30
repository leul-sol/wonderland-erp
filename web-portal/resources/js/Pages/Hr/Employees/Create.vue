<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import EmployeeFormFields from '../../../Components/Hr/EmployeeFormFields.vue';
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
            <EmployeeFormFields
                :form="form"
                :departments="departments"
                :positions="positions"
                show-hire-date
            />
            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create employee</button>
            </div>
        </form>
    </AppLayout>
</template>
