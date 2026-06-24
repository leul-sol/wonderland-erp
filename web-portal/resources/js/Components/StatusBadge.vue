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
        available: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        occupied: 'bg-amber-50 text-amber-900 ring-amber-200',
        maintenance: 'bg-slate-100 text-slate-700 ring-slate-200',
        confirmed: 'bg-blue-50 text-blue-800 ring-blue-200',
        checked_in: 'bg-teal-50 text-teal-800 ring-teal-200',
        checked_out: 'bg-slate-100 text-slate-600 ring-slate-200',
        open: 'bg-amber-50 text-amber-900 ring-amber-200',
        settled: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        closed: 'bg-slate-100 text-slate-600 ring-slate-200',
        pending_dept_head: 'bg-orange-50 text-orange-900 ring-orange-200',
        pending_finance: 'bg-purple-50 text-purple-900 ring-purple-200',
        pending_gm: 'bg-rose-50 text-rose-900 ring-rose-200',
        approved: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        received: 'bg-blue-50 text-blue-800 ring-blue-200',
        draft: 'bg-slate-100 text-slate-700 ring-slate-200',
        posted: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        active: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        archived: 'bg-slate-100 text-slate-600 ring-slate-200',
        pending: 'bg-amber-50 text-amber-900 ring-amber-200',
        pending_approval: 'bg-orange-50 text-orange-900 ring-orange-200',
        rejected: 'bg-red-50 text-red-800 ring-red-200',
        calculated: 'bg-blue-50 text-blue-800 ring-blue-200',
        paid: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        present: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        absent: 'bg-red-50 text-red-800 ring-red-200',
        leave: 'bg-purple-50 text-purple-900 ring-purple-200',
        half_day: 'bg-amber-50 text-amber-900 ring-amber-200',
    };

    return map[props.status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
});

const label = computed(() => props.status.replaceAll('_', ' '));
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
        :class="classes"
    >
        {{ label }}
    </span>
</template>
