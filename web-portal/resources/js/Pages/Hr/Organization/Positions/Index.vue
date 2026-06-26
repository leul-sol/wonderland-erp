<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import DataTable from '../../../../Components/DataTable.vue';
import PageHeader from '../../../../Components/PageHeader.vue';
import RowActions from '../../../../Components/RowActions.vue';
import AppLayout from '../../../../Layouts/AppLayout.vue';

const props = defineProps({
    positions: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const form = useForm({
    title: '',
    department_id: props.departments[0]?.id ?? '',
    grade: '',
    transport_allowance: '',
    housing_allowance: '',
});

const columns = [
    { key: 'title', label: 'Position', sortable: true },
    { key: 'department', label: 'Department', sortable: true },
    { key: 'grade', label: 'Grade' },
    { key: 'transport_allowance', label: 'Transport', class: 'text-right' },
    { key: 'housing_allowance', label: 'Housing', class: 'text-right' },
    { key: 'actions', label: '', class: 'text-right w-16' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Positions' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function submit() {
    form.post('/hr/positions', {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'grade', 'transport_allowance', 'housing_allowance'),
    });
}

function removePosition(position) {
    if (!window.confirm(`Delete position "${position.title}"? It must not be assigned to any employee.`)) {
        return;
    }

    router.delete(`/hr/positions/${position.id}`);
}
</script>

<template>
    <AppLayout title="Positions">
        <PageHeader
            title="Positions"
            subtitle="Job titles, grades, and allowance defaults by department"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/hr/departments" class="wh-btn-outline">Departments</Link>
                <Link href="/hr/employees" class="wh-btn-secondary">Employees</Link>
            </template>
        </PageHeader>

        <section v-if="canCreate" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">New position</h3>
            <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Title</label>
                    <input v-model="form.title" type="text" required maxlength="80" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Department</label>
                    <select v-model="form.department_id" required class="wh-input">
                        <option v-for="department in departments" :key="department.id" :value="department.id">
                            {{ department.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Grade</label>
                    <input v-model="form.grade" type="text" maxlength="10" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Transport allowance (ETB)</label>
                    <input v-model="form.transport_allowance" type="number" step="0.01" min="0" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Housing allowance (ETB)</label>
                    <input v-model="form.housing_allowance" type="number" step="0.01" min="0" class="wh-input" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="form.processing || departments.length === 0">
                        <Plus class="h-4 w-4" />
                        Add position
                    </button>
                </div>
            </form>
            <p v-if="departments.length === 0" class="mt-2 text-sm text-amber-700">
                Create a department before adding positions.
            </p>
        </section>

        <DataTable
            list-title="Position list"
            :columns="columns"
            :rows="positions"
            empty-message="No positions yet."
            selectable
        >
            <template #cell-department="{ row }">
                {{ row.department?.name ?? '—' }}
            </template>
            <template #cell-transport_allowance="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.transport_allowance) }}</span>
            </template>
            <template #cell-housing_allowance="{ row }">
                <span class="wh-money">ETB {{ formatMoney(row.housing_allowance) }}</span>
            </template>
            <template #cell-actions="{ row }">
                <RowActions
                    v-if="canUpdate || canDelete"
                    :items="[
                        ...(canUpdate ? [{ label: 'Edit', href: `/hr/positions/${row.id}/edit` }] : []),
                        ...(canDelete ? [{ label: 'Delete', onClick: () => removePosition(row) }] : []),
                    ]"
                />
            </template>
        </DataTable>
    </AppLayout>
</template>
