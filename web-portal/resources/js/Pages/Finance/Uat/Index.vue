<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    filters: { type: Object, default: () => ({}) },
    canUpdate: { type: Boolean, default: false },
    uat: { type: Object, default: () => ({ scenarios: [], meta: {} }) },
});

const activeScenario = ref(null);

const resultForm = useForm({
    status: 'passed',
    notes: '',
});

const columns = [
    { key: 'scenario_key', label: 'ID' },
    { key: 'title', label: 'Scenario' },
    { key: 'system', label: 'System' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '', class: 'text-right' },
];

function openRecord(scenario) {
    activeScenario.value = scenario;
    resultForm.status = 'passed';
    resultForm.notes = scenario.notes ?? '';
}

function submitResult() {
    resultForm.post(`/finance/uat/${activeScenario.value.id}/results`, {
        preserveScroll: true,
        onSuccess: () => {
            activeScenario.value = null;
        },
    });
}
</script>

<template>
    <AppLayout title="Acceptance testing">
        <PageHeader
            title="Acceptance testing"
            subtitle="Sign off scenarios before go-live — no technical tools required"
        >
            <template #actions>
                <Link href="/finance/reports" class="wh-btn-secondary">Financial reports</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="uat">
            <p v-if="uat.meta?.pass_rate_percent" class="mb-4 text-sm text-slate-600">
                Pass rate: {{ uat.meta.pass_rate_percent }}% ·
                {{ uat.meta.passed ?? 0 }} passed ·
                {{ uat.meta.pending ?? 0 }} pending
            </p>

            <DataTable
                list-title="Test scenarios"
                selectable
                :columns="columns"
                :rows="uat.scenarios ?? []"
                empty-message="No acceptance scenarios configured."
            >
                <template #cell-status="{ row }">
                    <StatusBadge :status="row.status" />
                </template>
                <template #cell-actions="{ row }">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="wh-btn-secondary text-xs"
                        @click="openRecord(row)"
                    >
                        Record result
                    </button>
                </template>
            </DataTable>
        </PageDataSection>

        <div
            v-if="activeScenario"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
            @click.self="activeScenario = null"
        >
            <div class="wh-card w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-slate-900">{{ activeScenario.title }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ activeScenario.scenario_key }}</p>
                <form class="mt-4 space-y-4" @submit.prevent="submitResult">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="uat-status">Result</label>
                        <select id="uat-status" v-model="resultForm.status" class="wh-input w-full">
                            <option value="passed">Passed</option>
                            <option value="failed">Failed</option>
                            <option value="blocked">Blocked</option>
                            <option value="skipped">Skipped</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700" for="uat-notes">Notes</label>
                        <textarea id="uat-notes" v-model="resultForm.notes" class="wh-input w-full" rows="3" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="wh-btn-secondary" @click="activeScenario = null">Cancel</button>
                        <button type="submit" class="wh-btn-primary" :disabled="resultForm.processing">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
