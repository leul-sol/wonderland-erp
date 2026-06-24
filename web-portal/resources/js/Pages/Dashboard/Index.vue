<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    welcome: {
        type: String,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-teal-700">Phase 3 · Inventory & procurement</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Welcome back</h2>
            <p class="mt-3 max-w-2xl text-slate-600">{{ welcome }}</p>

            <div v-if="roles.length" class="mt-6">
                <p class="text-sm font-medium text-slate-700">Your roles</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        v-for="role in roles"
                        :key="role"
                        class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-800"
                    >
                        {{ role }}
                    </span>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Front desk golden path</p>
                    <p class="mt-1 text-sm text-slate-600">Use Quick tasks: check in → post charges → settle → check out.</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Procurement</p>
                    <p class="mt-1 text-sm text-slate-600">Create PO → submit → tier approval → receive goods → settle payables.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
