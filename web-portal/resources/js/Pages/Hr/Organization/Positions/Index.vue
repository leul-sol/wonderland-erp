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
    canCreate: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
});

const positions = computed(() => props.pageLoad?.positions ?? []);
const departments = computed(() => props.pageLoad?.departments ?? []);

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingPosition = ref(null);
const pendingEditId = ref(null);

const createForm = useForm({
    title: '',
    department_id: '',
    grade: '',
    transport_allowance: '',
    housing_allowance: '',
});

const editForm = useForm({
    title: '',
    department_id: '',
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

function openCreateModal() {
    createForm.reset();
    createForm.department_id = departments.value[0]?.id ?? '';
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createForm.reset();
    createForm.clearErrors();
}

function submitCreate() {
    createForm.post('/hr/positions', {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
}

function openEditModal(position) {
    editingPosition.value = position;
    editForm.title = position.title ?? '';
    editForm.department_id = position.department_id ?? position.department?.id ?? '';
    editForm.grade = position.grade ?? '';
    editForm.transport_allowance = position.transport_allowance ?? '';
    editForm.housing_allowance = position.housing_allowance ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingPosition.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit() {
    if (!editingPosition.value) {
        return;
    }

    editForm.patch(`/hr/positions/${editingPosition.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
}

async function removePosition(position) {
    const confirmed = await confirmAction({
        title: 'Delete position',
        message: `Delete position "${position.title}"? It must not be assigned to any employee.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/hr/positions/${position.id}`);
}

useQueryModal(showCreateModal, {
    when: () => props.canCreate,
    onOpen: openCreateModal,
});

useQueryModal(showEditModal, {
    expected: 'edit',
    when: () => props.canUpdate,
    onOpen: (params) => {
        const id = Number.parseInt(params.get('id') ?? '', 10);

        if (!id) {
            return;
        }

        const position = positions.value.find((row) => row.id === id);

        if (position) {
            openEditModal(position);
        } else {
            pendingEditId.value = id;
        }
    },
});

watch(positions, (rows) => {
    if (!pendingEditId.value || rows.length === 0) {
        return;
    }

    const position = rows.find((row) => row.id === pendingEditId.value);

    if (position) {
        openEditModal(position);
        pendingEditId.value = null;
    }
});
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
                <button v-if="canCreate" type="button" class="wh-btn-primary" @click="openCreateModal">
                    <Plus class="h-4 w-4" />
                    Add position
                </button>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
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
                        ...(canUpdate ? [{ label: 'Edit', onClick: () => openEditModal(row) }] : []),
                        ...(canDelete ? [{ label: 'Delete', onClick: () => removePosition(row) }] : []),
                    ]"
                />
            </template>
        </DataTable>
        </PageDataSection>

        <FormModal
            v-if="canCreate"
            :open="showCreateModal"
            title="Add position"
            subtitle="Job title linked to a department with default allowances"
            size="lg"
            @close="closeCreateModal"
        >
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <FormLabel for="pos_title" required>Title</FormLabel>
                        <input id="pos_title" v-model="createForm.title" type="text" required maxlength="80" class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="pos_department" required>Department</FormLabel>
                        <select id="pos_department" v-model="createForm.department_id" required class="wh-input">
                            <option v-for="department in departments" :key="department.id" :value="department.id">
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <FormLabel for="pos_grade">Grade</FormLabel>
                        <input id="pos_grade" v-model="createForm.grade" type="text" maxlength="10" class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="pos_transport">Transport allowance (ETB)</FormLabel>
                        <input id="pos_transport" v-model="createForm.transport_allowance" type="number" step="0.01" min="0" class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="pos_housing">Housing allowance (ETB)</FormLabel>
                        <input id="pos_housing" v-model="createForm.housing_allowance" type="number" step="0.01" min="0" class="wh-input" />
                    </div>
                </div>
                <p v-if="departments.length === 0" class="text-sm text-amber-700">
                    Create a department before adding positions.
                </p>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button type="button" class="wh-btn-secondary" @click="closeCreateModal">Cancel</button>
                    <button
                        type="button"
                        class="wh-btn-primary"
                        :disabled="createForm.processing || departments.length === 0"
                        @click="submitCreate"
                    >
                        Add position
                    </button>
                </div>
            </template>
        </FormModal>

        <FormModal
            v-if="canUpdate"
            :open="showEditModal"
            :title="`Edit ${editingPosition?.title ?? 'position'}`"
            subtitle="Update title, department, grade, or allowances"
            size="lg"
            @close="closeEditModal"
        >
            <form class="space-y-4" @submit.prevent="submitEdit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <FormLabel for="edit_pos_title" required>Title</FormLabel>
                        <input id="edit_pos_title" v-model="editForm.title" type="text" required maxlength="80" class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="edit_pos_department" required>Department</FormLabel>
                        <select id="edit_pos_department" v-model="editForm.department_id" required class="wh-input">
                            <option v-for="department in departments" :key="department.id" :value="department.id">
                                {{ department.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <FormLabel for="edit_pos_grade">Grade</FormLabel>
                        <input id="edit_pos_grade" v-model="editForm.grade" type="text" maxlength="10" class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="edit_pos_transport">Transport allowance (ETB)</FormLabel>
                        <input id="edit_pos_transport" v-model="editForm.transport_allowance" type="number" step="0.01" min="0" class="wh-input" />
                    </div>
                    <div>
                        <FormLabel for="edit_pos_housing">Housing allowance (ETB)</FormLabel>
                        <input id="edit_pos_housing" v-model="editForm.housing_allowance" type="number" step="0.01" min="0" class="wh-input" />
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
