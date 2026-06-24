<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    defaultPeriodStart: { type: String, required: true },
    defaultPeriodEnd: { type: String, required: true },
});

const form = useForm({
    period_start: props.defaultPeriodStart,
    period_end: props.defaultPeriodEnd,
});

function submit() {
    form.post('/payroll/runs');
}
</script>

<template>
    <AppLayout title="Create payroll run">
        <PageHeader title="Create payroll run" subtitle="Generates draft lines for all active employees">
            <template #actions>
                <Link href="/payroll/runs" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-lg p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="period_start" class="mb-1 block text-sm font-medium text-slate-700">Period start</label>
                    <input id="period_start" v-model="form.period_start" type="date" required class="wh-input" />
                </div>
                <div>
                    <label for="period_end" class="mb-1 block text-sm font-medium text-slate-700">Period end</label>
                    <input id="period_end" v-model="form.period_end" type="date" required class="wh-input" />
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Create draft run</button>
            </div>
        </form>
    </AppLayout>
</template>
