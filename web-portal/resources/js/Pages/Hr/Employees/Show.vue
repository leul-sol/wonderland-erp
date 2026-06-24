<script setup>
import { Link } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    employee: { type: Object, required: true },
    platformUser: { type: Object, default: null },
});

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}
</script>

<template>
    <AppLayout :title="employee.full_name">
        <PageHeader
            :title="employee.full_name"
            :subtitle="`${employee.employee_number} · ${employee.department?.name ?? 'No department'}`"
        >
            <template #actions>
                <StatusBadge :status="employee.status" />
                <Link href="/hr/employees" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Employment</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Job title</dt>
                        <dd class="font-medium text-slate-900">{{ employee.job_title ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Position</dt>
                        <dd class="font-medium text-slate-900">{{ employee.position?.title ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Base salary</dt>
                        <dd class="wh-money font-medium text-slate-900">ETB {{ formatMoney(employee.base_salary) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Hire date</dt>
                        <dd class="font-medium text-slate-900">{{ employee.hire_date ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Pension</dt>
                        <dd class="font-medium capitalize text-slate-900">{{ employee.pension_category?.replaceAll('_', ' ') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="wh-card p-4">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Platform user (S1)</h3>
                <div v-if="platformUser" class="rounded-lg border border-teal-100 bg-teal-50 p-4 text-sm">
                    <p class="font-medium text-teal-900">{{ platformUser.display_name }}</p>
                    <p class="mt-1 text-teal-800">Username: {{ platformUser.username }}</p>
                    <p class="text-teal-800">Email: {{ platformUser.email }}</p>
                    <p class="mt-2 text-xs text-teal-700">User ID #{{ platformUser.id }} · read-only link</p>
                </div>
                <p v-else class="text-sm text-slate-600">
                    No platform user linked yet. Provisioning runs via the employee-created event — refresh in a moment.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
