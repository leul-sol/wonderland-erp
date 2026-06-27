<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import DataTable from '../../../../Components/DataTable.vue';
import PageDataSection from '../../../../Components/PageDataSection.vue';
import PageHeader from '../../../../Components/PageHeader.vue';
import RowActions from '../../../../Components/RowActions.vue';
import { confirmAction } from '../../../../composables/useConfirm';
import AppLayout from '../../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    canWrite: { type: Boolean, default: false },
});

const departments = computed(() => props.pageLoad?.departments ?? []);
const employees = computed(() => props.pageLoad?.employees ?? []);

const form = useForm({
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

function submit() {
    form.post('/hr/departments', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
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
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Code</label>
                    <input v-model="form.code" type="text" required maxlength="20" class="wh-input uppercase" placeholder="FO" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
                    <input v-model="form.name" type="text" required maxlength="100" class="wh-input" placeholder="Front Office" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Department head</label>
                    <select v-model="form.head_employee_id" class="wh-input">
                        <option value="">None</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                            {{ employee.full_name }}
                        </option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="form.processing">
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
                        { label: 'Edit', href: `/hr/departments/${row.id}/edit` },
                        { label: 'Delete', onClick: () => removeDepartment(row) },
                    ]"
                />
            </template>
        </DataTable>
        </PageDataSection>
    </AppLayout>
</template>
