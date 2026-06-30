<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import EmployeeFormFields from '../../../Components/Hr/EmployeeFormFields.vue';
import FormModal from '../../../Components/FormModal.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { useQueryModal } from '../../../composables/useQueryModal';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    canCreate: { type: Boolean, default: false },
    defaultHireDate: { type: String, default: '' },
});

const employees = computed(() => props.pageLoad?.employees ?? []);
const departments = computed(() => props.pageLoad?.departments ?? []);
const positions = computed(() => props.pageLoad?.positions ?? []);

const showCreateModal = ref(false);

const form = useForm({
    full_name: '',
    email: '',
    department_id: '',
    position_id: '',
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
    form.department_id = departments.value[0]?.id ?? '';
    form.position_id = positions.value[0]?.id ?? '';
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

useQueryModal(showCreateModal, {
    when: () => props.canCreate,
    onOpen: openCreateModal,
});
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

        <PageDataSection keys="pageLoad">
        <DataTable list-title="Employee list" :columns="columns" :rows="employees" empty-message="No employees yet." selectable>
            <template #empty>
                <EmptyState
                    title="No employees yet"
                    description="Add workforce records here. When an email is provided, the system provisions a portal login with the default role you choose."
                    variant="table"
                >
                    <template #action>
                        <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">
                            Add your first employee
                        </button>
                    </template>
                </EmptyState>
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
        </PageDataSection>

        <FormModal
            v-if="canCreate"
            :open="showCreateModal"
            title="Add employee"
            subtitle="Creates workforce record; S1 user is provisioned asynchronously"
            size="lg"
            @close="closeCreateModal"
        >
            <form @submit.prevent="submitCreate">
                <EmployeeFormFields
                    :form="form"
                    :departments="departments"
                    :positions="positions"
                    show-hire-date
                />
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
