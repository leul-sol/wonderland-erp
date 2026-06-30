<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import DataTable from '../../../../Components/DataTable.vue';
import FormLabel from '../../../../Components/FormLabel.vue';
import FormModal from '../../../../Components/FormModal.vue';
import PageDataSection from '../../../../Components/PageDataSection.vue';
import PageHeader from '../../../../Components/PageHeader.vue';
import RowActions from '../../../../Components/RowActions.vue';
import { confirmAction } from '../../../../composables/useConfirm';
import { useQueryModal } from '../../../../composables/useQueryModal';
import AppLayout from '../../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    canWrite: { type: Boolean, default: false },
});

const departments = computed(() => props.pageLoad?.departments ?? []);
const employees = computed(() => props.pageLoad?.employees ?? []);

const showEditModal = ref(false);
const editingDepartment = ref(null);
const pendingEditId = ref(null);

const createForm = useForm({
    code: '',
    name: '',
    head_employee_id: '',
});

const editForm = useForm({
    code: '',
    name: '',
    head_employee_id: '',
});

const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Department', sortable: true },
    { key: 'head', label: 'Department head' },
    { key: 'actions', label: '', class: 'text-right w-16' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Departments' },
];

function employeeName(id) {
    if (!id) {
        return '—';
    }

    return employees.value.find((employee) => employee.id === id)?.full_name ?? `Employee #${id}`;
}

function submitCreate() {
    createForm.post('/hr/departments', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function openEditModal(department) {
    editingDepartment.value = department;
    editForm.code = department.code ?? '';
    editForm.name = department.name ?? '';
    editForm.head_employee_id = department.head_employee_id ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingDepartment.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit() {
    if (!editingDepartment.value) {
        return;
    }

    editForm.patch(`/hr/departments/${editingDepartment.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

async function removeDepartment(department) {
    const confirmed = await confirmAction({
        title: 'Delete department',
        message: `Delete department "${department.name}"? It must have no assigned employees.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/hr/departments/${department.id}`);
}

useQueryModal(showEditModal, {
    expected: 'edit',
    onOpen: (params) => {
        const id = Number.parseInt(params.get('id') ?? '', 10);

        if (!id) {
            return;
        }

        const department = departments.value.find((row) => row.id === id);

        if (department) {
            openEditModal(department);
        } else {
            pendingEditId.value = id;
        }
    },
});

watch(departments, (rows) => {
    if (!pendingEditId.value || rows.length === 0) {
        return;
    }

    const department = rows.find((row) => row.id === pendingEditId.value);

    if (department) {
        openEditModal(department);
        pendingEditId.value = null;
    }
});
</script>

<template>
    <AppLayout title="Departments">
        <PageHeader
            title="Departments"
            subtitle="Organizational units for workforce and leave scoping"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/hr/positions" class="wh-btn-outline">Positions</Link>
                <Link href="/hr/employees" class="wh-btn-secondary">Employees</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <section v-if="canWrite" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">New department</h3>
            <form class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submitCreate">
                <div>
                    <FormLabel for="dept_code" required compact>Code</FormLabel>
                    <input id="dept_code" v-model="createForm.code" type="text" required maxlength="20" class="wh-input uppercase" placeholder="FO" />
                </div>
                <div>
                    <FormLabel for="dept_name" required compact>Name</FormLabel>
                    <input id="dept_name" v-model="createForm.name" type="text" required maxlength="100" class="wh-input" placeholder="Front Office" />
                </div>
                <div>
                    <FormLabel for="dept_head" compact>Department head</FormLabel>
                    <select id="dept_head" v-model="createForm.head_employee_id" class="wh-input">
                        <option value="">None</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="wh-btn-primary w-full sm:w-auto" :disabled="createForm.processing">
                        <Plus class="h-4 w-4" />
                        Add department
                    </button>
                </div>
            </form>
        </section>

        <DataTable
            list-title="Department list"
            :columns="columns"
            :rows="departments"
            empty-message="No departments yet."
            selectable
        >
            <template #cell-head="{ row }">
                {{ employeeName(row.head_employee_id) }}
            </template>
            <template #cell-actions="{ row }">
                <RowActions
                    v-if="canWrite"
                    :items="[
                        { label: 'Edit', onClick: () => openEditModal(row) },
                        { label: 'Delete', onClick: () => removeDepartment(row) },
                    ]"
                />
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canWrite"
            :open="showEditModal"
            :title="`Edit ${editingDepartment?.name ?? 'department'}`"
            subtitle="Update department code, name, or head"
            @close="closeEditModal"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <FormLabel for="edit_dept_code" required>Code</FormLabel>
                        <input id="edit_dept_code" v-model="editForm.code" type="text" required maxlength="20" class="wh-input uppercase" />
                    </div>
                    <div>
                        <FormLabel for="edit_dept_name" required>Name</FormLabel>
                        <input id="edit_dept_name" v-model="editForm.name" type="text" required maxlength="100" class="wh-input" />
                    </div>
                    <div class="sm:col-span-2">
                        <FormLabel for="edit_dept_head">Department head</FormLabel>
                        <select id="edit_dept_head" v-model="editForm.head_employee_id" class="wh-input">
                            <option value="">None</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.full_name }}
                            </option>
                        </select>
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeEditModal">Cancel</button>
                    <button type="button" class="wh-btn-primary" :disabled="editForm.processing" @click="submitEdit">Save changes</button>
                </div>
            </template>
        </FormModal>
    </AppLayout>
</template>
