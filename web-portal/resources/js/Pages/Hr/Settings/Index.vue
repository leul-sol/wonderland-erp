<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import DataTable from '../../../Components/DataTable.vue';
import PageDataSection from '../../../Components/PageDataSection.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import RowActions from '../../../Components/RowActions.vue';
import { confirmAction } from '../../../composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pageLoad: { type: Object, default: null },
    canReadLeaveTypes: { type: Boolean, default: false },
    canReadOvertimeRates: { type: Boolean, default: false },
    canUpdateOvertimeRates: { type: Boolean, default: false },
    canReadAssetTypes: { type: Boolean, default: false },
    canWriteAssetTypes: { type: Boolean, default: false },
});

const leaveTypes = computed(() => props.pageLoad?.leaveTypes ?? []);
const overtimeRates = computed(() => props.pageLoad?.overtimeRates ?? []);
const assetTypes = computed(() => props.pageLoad?.assetTypes ?? []);

const assetForm = useForm({
    name: '',
    description: '',
});

const leaveColumns = [
    { key: 'code', label: 'Code' },
    { key: 'name', label: 'Leave type' },
    { key: 'max_days_per_year', label: 'Max days / year', class: 'text-right' },
    { key: 'paid', label: 'Paid' },
];

const assetColumns = [
    { key: 'name', label: 'Asset type' },
    { key: 'description', label: 'Description' },
    { key: 'actions', label: '', class: 'text-right w-16' },
];

const breadcrumbs = [
    { label: 'Dashboard', href: '/' },
    { label: 'HR', href: '/hr/employees' },
    { label: 'Settings' },
];

function submitAssetType() {
    assetForm.post('/hr/settings/asset-types', {
        preserveScroll: true,
        onSuccess: () => assetForm.reset(),
    });
}

async function removeAssetType(assetType) {
    const confirmed = await confirmAction({
        title: 'Delete asset type',
        message: `Delete asset type "${assetType.name}"? It must not be assigned to any employee asset.`,
        confirmLabel: 'Delete',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/hr/settings/asset-types/${assetType.id}`);
}

function saveOvertimeRate(rate) {
    useForm({ multiplier: rate.multiplier }).patch(`/hr/settings/overtime-rates/${rate.id}`, {
        preserveScroll: true,
    });
}

function categoryLabel(category) {
    return String(category ?? '').replaceAll('_', ' ');
}
</script>

<template>
    <AppLayout title="HR settings">
        <PageHeader
            title="HR settings"
            subtitle="Leave catalog, overtime multipliers, and asset types"
            :breadcrumbs="breadcrumbs"
        >
            <template #actions>
                <Link href="/hr/employees" class="wh-btn-secondary">Employees</Link>
            </template>
        </PageHeader>

        <PageDataSection keys="pageLoad">
        <section v-if="canReadLeaveTypes" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Leave types</h3>
            <p class="mb-4 text-sm text-slate-600">Read-only catalog seeded in S2. Balances accrue per type.</p>
            <DataTable :columns="leaveColumns" :rows="leaveTypes" empty-message="No leave types configured.">
                <template #cell-paid="{ row }">
                    {{ row.paid ? 'Yes' : 'No' }}
                </template>
            </DataTable>
        </section>

        <section v-if="canReadOvertimeRates" class="wh-card mb-6 p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Overtime rate multipliers</h3>
            <div class="space-y-3">
                <div
                    v-for="rate in overtimeRates"
                    :key="rate.id"
                    class="flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 p-3"
                >
                    <div class="min-w-[140px] flex-1">
                        <p class="text-sm font-medium capitalize text-slate-900">{{ categoryLabel(rate.category) }}</p>
                        <p class="text-xs text-slate-500">Applied to approved overtime in payroll</p>
                    </div>
                    <div class="w-28">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Multiplier</label>
                        <input
                            v-model="rate.multiplier"
                            type="number"
                            step="0.01"
                            min="1"
                            max="5"
                            class="wh-input"
                            :disabled="!canUpdateOvertimeRates"
                        />
                    </div>
                    <button
                        v-if="canUpdateOvertimeRates"
                        type="button"
                        class="wh-btn-secondary text-xs"
                        @click="saveOvertimeRate(rate)"
                    >
                        Save
                    </button>
                </div>
            </div>
        </section>

        <section v-if="canReadAssetTypes" class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Asset types</h3>

            <form v-if="canWriteAssetTypes" class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="submitAssetType">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
                    <input v-model="assetForm.name" type="text" required maxlength="80" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Description</label>
                    <input v-model="assetForm.description" type="text" maxlength="255" class="wh-input" />
                </div>
                <div class="flex items-end">
                    <button type="submit" class="wh-btn-primary" :disabled="assetForm.processing">
                        <Plus class="h-4 w-4" />
                        Add asset type
                    </button>
                </div>
            </form>

            <DataTable :columns="assetColumns" :rows="assetTypes" empty-message="No asset types yet.">
                <template #cell-actions="{ row }">
                    <RowActions
                        v-if="canWriteAssetTypes"
                        :items="[{ label: 'Delete', onClick: () => removeAssetType(row) }]"
                    />
                </template>
            </DataTable>
        </section>
        </PageDataSection>
    </AppLayout>
</template>
