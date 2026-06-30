<script setup>
import FormLabel from '../FormLabel.vue';
import { PORTAL_ROLES } from '../../constants/portalRoles';

defineProps({
    form: { type: Object, required: true },
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    showHireDate: { type: Boolean, default: false },
    showDefaultRole: { type: Boolean, default: true },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <FormLabel for="employee_full_name" required>Full name</FormLabel>
            <input id="employee_full_name" v-model="form.full_name" type="text" required class="wh-input" />
        </div>
        <div>
            <FormLabel for="employee_email">Email</FormLabel>
            <input id="employee_email" v-model="form.email" type="email" class="wh-input" />
        </div>
        <div v-if="showHireDate">
            <FormLabel for="employee_hire_date">Hire date</FormLabel>
            <input id="employee_hire_date" v-model="form.hire_date" type="date" class="wh-input" />
        </div>
        <div v-else>
            <FormLabel for="employee_job_title">Job title</FormLabel>
            <input id="employee_job_title" v-model="form.job_title" type="text" class="wh-input" />
        </div>
        <div>
            <FormLabel for="employee_department_id">Department</FormLabel>
            <select id="employee_department_id" v-model="form.department_id" class="wh-input">
                <option value="">None</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
            </select>
        </div>
        <div>
            <FormLabel for="employee_position_id">Position</FormLabel>
            <select id="employee_position_id" v-model="form.position_id" class="wh-input">
                <option value="">None</option>
                <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.title }}</option>
            </select>
        </div>
        <div v-if="showHireDate">
            <FormLabel for="employee_job_title">Job title</FormLabel>
            <input id="employee_job_title" v-model="form.job_title" type="text" class="wh-input" />
        </div>
        <div>
            <FormLabel for="employee_base_salary" required>Base salary (ETB)</FormLabel>
            <input id="employee_base_salary" v-model="form.base_salary" type="number" step="0.01" min="0" required class="wh-input" />
        </div>
        <div>
            <FormLabel for="employee_pension_category">Pension</FormLabel>
            <select id="employee_pension_category" v-model="form.pension_category" class="wh-input">
                <option value="covered">Covered</option>
                <option value="not_covered">Not covered</option>
            </select>
        </div>
        <div v-if="showDefaultRole" class="sm:col-span-2">
            <FormLabel for="employee_default_role">Default portal role</FormLabel>
            <select id="employee_default_role" v-model="form.default_role" class="wh-input">
                <option v-for="role in PORTAL_ROLES" :key="role.slug" :value="role.slug">{{ role.label }}</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">
                Role assigned to the login account when HR provisions a platform user for this employee.
                Super admin accounts should still be created in Administration → Users.
            </p>
        </div>
    </div>
</template>
