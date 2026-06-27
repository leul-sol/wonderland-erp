<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    filters: { type: Object, default: () => ({}) },
    canUpdate: { type: Boolean, default: false },
    rtm: { type: Object, default: () => ({ entries: [], meta: {} }) },
});

const editing = ref(null);
const editForm = useForm({ status: 'implemented', notes: '' });

const columns = [
    { key: 'requirement_key', label: 'Requirement' },
    { key: 'title', label: 'Title' },
    { key: 'system', label: 'System' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function openEdit(entry) {
    editing.value = entry;
    editForm.status = entry.status;
    editForm.notes = entry.notes ?? '';
}

function submitEdit() {
    editForm.put(`/finance/rtm/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
        },
    });
}
</script>

<template>
    <AppLayout title="Requirements traceability">
        <PageHeader title="Requirements traceability" subtitle="Implementation status (for project team — not daily hotel use)">
            <template #actions>
                <Link href="/finance/uat" class="wh-btn-secondary">Acceptance testing</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="rtm">
            <DataTable
                list-title="Requirements"
                selectable
                :columns="columns"
                :rows="rtm.entries ?? []"
                empty-message="No traceability entries."
            >
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="wh-btn-secondary text-xs"
                        @click="openEdit(row)"
                    >
                        Update
                    </button>
                </template>
            </DataTable>
        </PageDataSection>

        <div
            v-if="editing"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
            @click.self="editing = null"
        >
            <div class="wh-card w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ editing.requirement_key }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="submitEdit">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="rtm-status">Status</label>
                        <select id="rtm-status" v-model="editForm.status" class="wh-input w-full">
                            <option value="planned">Planned</option>
                            <option value="in_progress">In progress</option>
                            <option value="implemented">Implemented</option>
                            <option value="verified">Verified</option>
                            <option value="deferred">Deferred</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="rtm-notes">Notes</label>
                        <textarea id="rtm-notes" v-model="editForm.notes" class="wh-input w-full" rows="3" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="wh-btn-secondary" @click="editing = null">Cancel</button>
                        <button type="submit" class="wh-btn-primary" :disabled="editForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
