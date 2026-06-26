<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    departments: { type: Array, default: () => [] },
    positions: { type: Array, default: () => [] },
    defaultHireDate: { type: String, default: '' },
});

const showCreateModal = ref(false);

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

const columns = [
    { key: 'employee_number', label: 'ID', sortable: true },
    { key: 'full_name', label: 'Name', sortable: true },
    { key: 'department', label: 'Department', sortable: true },
    { key: 'job_title', label: 'Title' },
    { key: 'base_salary', label: 'Base salary', class: 'text-right' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Action', class: 'text-right w-16' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function openCreateModal() {
    form.reset();
    form.department_id = props.departments[0]?.id ?? '';
    form.position_id = props.positions[0]?.id ?? '';
    form.pension_category = 'covered';
    form.default_role = 'report_viewer';
    form.hire_date = props.defaultHireDate;
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    form.post('/hr/employees', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

useQueryModal(showCreateModal, { onOpen: openCreateModal });
</script>

<template>
    <AppLayout title="Employees">
        <PageHeader title="Employees" subtitle="Workforce records and platform user provisioning" :show-export="true">
            <template #actions>
                <Link href="/hr/leave-requests" class="wh-btn-outline">Leave</Link>
                <Link href="/hr/departments" class="wh-btn-outline">Departments</Link>
                <Link href="/hr/attendance" class="wh-btn-outline">Attendance</Link>
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">
                    <Plus class="h-4 w-4" />
                    Add employee
                </button>
            </template>
        </PageHeader>

        <DataTable list-title="Employee list" :columns="columns" :rows="employees" empty-message="No employees yet." selectable>
            <template #empty>
                <p>No employees yet.</p>
                <button v-if="canCreate" type="button" class="wh-btn-primary mt-3" @click="openCreateModal">Add your first employee</button>
            </template>
            <template #cell-employee_number="{ row }">
                <Link :href="`/hr/employees/${row.id}`" class="wh-table-link">{{ row.employee_number }}</Link>
            </template>
            <template #cell-department="{ row }">
                {{ row.department?.name ?? '—' }}
            </template>
            <template #cell-base_salary="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.base_salary) }}</span>
            </template>
            <template #cell-status="{ row }">
                <StatusBadge :status="row.status" />
            </template>
            <template #cell-actions="{ row }">
                <RowActions :items="[{ label: 'Open', href: `/hr/employees/${row.id}` }]" />
            </template>
        </DataTable>

        <FormModal :open="showCreateModal" title="Add employee" subtitle="Creates workforce record; S1 user is provisioned asynchronously" size="lg" @close="closeCreateModal">
            <form class="space-y-4" @submit.prevent="submitCreate">
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
                            <option v-for="position in positions" :key="position.id" :value="position.id">{{ position.title }}</option>
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
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="form.processing" @click="submitCreate">Create employee</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
