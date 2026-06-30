<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import EmployeeFormFields from '../../../Components/Hr/EmployeeFormFields.vue';
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
            <EmployeeFormFields
                :form="form"
                :departments="departments"
                :positions="positions"
            />
            <div class="mt-6 flex justify-end gap-3">
                <Link :href="`/hr/employees/${employee.id}`" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save changes</button>
            </div>
        </form>
    </AppLayout>
</template>
