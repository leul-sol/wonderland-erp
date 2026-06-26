<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    defaultPeriodStart: { type: String, required: true },
    defaultPeriodEnd: { type: String, required: true },
    maxPeriodEnd: { type: String, required: true },
    canRecordAttendance: { type: Boolean, default: false },
});

const page = usePage();

const form = useForm({
    period_start: props.defaultPeriodStart,
    period_end: props.defaultPeriodEnd,
});

const attendanceGap = computed(() => page.props.flash?.attendanceGap ?? null);

const periodEndInFuture = computed(() => form.period_end > props.maxPeriodEnd);

const attendanceFixHref = computed(() => {
    if (!attendanceGap.value?.work_date) {
        return '/hr/attendance';
    }

    return `/hr/attendance?work_date=${attendanceGap.value.work_date}`;
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

        <section class="wh-card mb-6 p-4 text-sm text-slate-600">
            <h3 class="mb-2 font-semibold text-slate-800">Before you create a run</h3>
            <ul class="list-disc space-y-1 pl-5">
                <li>Every <strong>active employee</strong> needs a weekday attendance record for each day in the period.</li>
                <li>Period end cannot be in the future — use through today (or the last completed weekday).</li>
                <li>Statuses <strong>present</strong>, <strong>leave</strong>, and <strong>half day</strong> count toward pay; <strong>absent</strong> does not.</li>
            </ul>
            <p v-if="canRecordAttendance" class="mt-3">
                <Link href="/hr/attendance" class="wh-table-link">Open attendance</Link>
                to fill gaps before running payroll.
            </p>
        </section>

        <section
            v-if="attendanceGap"
            class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
        >
            <p class="font-medium">Missing attendance blocked this run</p>
            <p class="mt-1">
                Record attendance for <strong>{{ attendanceGap.employee_name }}</strong> on
                <strong>{{ attendanceGap.work_date }}</strong>, then try again.
            </p>
            <Link v-if="canRecordAttendance" :href="attendanceFixHref" class="mt-3 inline-block wh-btn-secondary text-xs">
                Record attendance for this date
            </Link>
        </section>

        <form class="wh-card mx-auto max-w-lg p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="period_start" class="mb-1 block text-sm font-medium text-slate-700">Period start</label>
                    <input id="period_start" v-model="form.period_start" type="date" required class="wh-input" />
                </div>
                <div>
                    <label for="period_end" class="mb-1 block text-sm font-medium text-slate-700">Period end</label>
                    <input
                        id="period_end"
                        v-model="form.period_end"
                        type="date"
                        required
                        :max="maxPeriodEnd"
                        class="wh-input"
                    />
                </div>
            </div>
            <p v-if="periodEndInFuture" class="mt-3 text-sm text-amber-700">
                Period end is after {{ maxPeriodEnd }}. Future dates require attendance that does not exist yet.
            </p>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing || periodEndInFuture">
                    Create draft run
                </button>
            </div>
        </form>
    </AppLayout>
</template>
