<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../../Components/PageHeader.vue';
import AppLayout from '../../../../Layouts/AppLayout.vue';

const props = defineProps({
    department: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
});

const form = useForm({
    code: props.department.code ?? '',
    name: props.department.name ?? '',
    head_employee_id: props.department.head_employee_id ?? '',
});

function submit() {
    form.patch(`/hr/departments/${props.department.id}`);
}
</script>

<template>
    <AppLayout :title="`Edit ${department.name}`">
        <PageHeader :title="`Edit ${department.name}`" :subtitle="department.code">
            <template #actions>
                <Link href="/hr/departments" class="wh-btn-secondary">Back to departments</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-2xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="code" class="mb-1 block text-sm font-medium text-slate-700">Code</label>
                    <input id="code" v-model="form.code" type="text" required maxlength="20" class="wh-input uppercase" />
                </div>
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="name" v-model="form.name" type="text" required maxlength="100" class="wh-input" />
                </div>
                <div class="sm:col-span-2">
                    <label for="head_employee_id" class="mb-1 block text-sm font-medium text-slate-700">Department head</label>
                    <select id="head_employee_id" v-model="form.head_employee_id" class="wh-input">
                        <option value="">None</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <Link href="/hr/departments" class="wh-btn-secondary">Cancel</Link>
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save changes</button>
            </div>
        </form>
    </AppLayout>
</template>
