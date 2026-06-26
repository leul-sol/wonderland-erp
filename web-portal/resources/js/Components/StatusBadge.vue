<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const classes = computed(() => {
    const map = {
        available: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        occupied: 'bg-amber-50 text-amber-800 ring-amber-200',
        maintenance: 'bg-slate-100 text-slate-700 ring-slate-200',
        confirmed: 'bg-blue-50 text-blue-700 ring-blue-200',
        checked_in: 'bg-teal-50 text-teal-700 ring-teal-200',
        checked_out: 'bg-slate-100 text-slate-600 ring-slate-200',
        open: 'bg-amber-50 text-amber-800 ring-amber-200',
        settled: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        closed: 'bg-slate-100 text-slate-600 ring-slate-200',
        pending_dept_head: 'bg-orange-50 text-orange-800 ring-orange-200',
        pending_finance: 'bg-purple-50 text-purple-800 ring-purple-200',
        pending_gm: 'bg-rose-50 text-rose-800 ring-rose-200',
        approved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        received: 'bg-blue-50 text-blue-700 ring-blue-200',
        draft: 'bg-slate-100 text-slate-700 ring-slate-200',
        posted: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        archived: 'bg-slate-100 text-slate-600 ring-slate-200',
        pending: 'bg-amber-50 text-amber-800 ring-amber-200',
        pending_approval: 'bg-orange-50 text-orange-800 ring-orange-200',
        rejected: 'bg-red-50 text-red-700 ring-red-200',
        calculated: 'bg-blue-50 text-blue-700 ring-blue-200',
        paid: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        present: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        absent: 'bg-red-50 text-red-700 ring-red-200',
        leave: 'bg-purple-50 text-purple-800 ring-purple-200',
        half_day: 'bg-amber-50 text-amber-800 ring-amber-200',
        partial: 'bg-amber-50 text-amber-800 ring-amber-200',
        accruing: 'bg-blue-50 text-blue-700 ring-blue-200',
        posted_to_payroll: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        closing: 'bg-orange-50 text-orange-800 ring-orange-200',
        locked: 'bg-slate-100 text-slate-600 ring-slate-200',
        inactive: 'bg-red-50 text-red-700 ring-red-200',
        written_off: 'bg-red-50 text-red-700 ring-red-200',
    };

    return map[props.status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
});

const dotClass = computed(() => {
    const map = {
        active: 'bg-emerald-500',
        available: 'bg-emerald-500',
        approved: 'bg-emerald-500',
        posted: 'bg-emerald-500',
        paid: 'bg-emerald-500',
        present: 'bg-emerald-500',
        settled: 'bg-emerald-500',
        inactive: 'bg-red-500',
        rejected: 'bg-red-500',
        absent: 'bg-red-500',
        written_off: 'bg-red-500',
        pending: 'bg-amber-500',
        open: 'bg-amber-500',
        occupied: 'bg-amber-500',
    };

    return map[props.status] ?? 'bg-slate-400';
});

const label = computed(() => props.status.replaceAll('_', ' '));
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset"
        :class="classes"
    >
        <span class="wh-status-dot" :class="dotClass" />
        {{ label }}
    </span>
</template>
