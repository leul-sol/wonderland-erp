<script setup>
import { Link } from '@inertiajs/vue3';
import ApprovalList from '../../Components/Dashboard/ApprovalList.vue';
import DateRangeFilter from '../../Components/Dashboard/DateRangeFilter.vue';
import KpiStatCard from '../../Components/Dashboard/KpiStatCard.vue';
import QuickLinksGrid from '../../Components/Dashboard/QuickLinksGrid.vue';
import RevenueBySourceChart from '../../Components/Dashboard/RevenueBySourceChart.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    persona: { type: String, default: 'default' },
    user_name: { type: String, default: 'User' },
    roles: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ from: '', to: '', label: '' }) },
    kpis: { type: Array, default: () => [] },
    secondary_kpis: { type: Array, default: () => [] },
    quick_links: { type: Array, default: () => [] },
    approvals: { type: Array, default: () => [] },
    notices: { type: Array, default: () => [] },
    occupancy: { type: Object, default: null },
    attendance: { type: Object, default: null },
    revenue_chart: { type: Object, default: null },
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <DateRangeFilter
                :from="filters.from"
                :to="filters.to"
                :label="filters.label"
            />
            <span
                v-for="role in roles.slice(0, 1)"
                :key="role"
                class="shrink-0 rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium capitalize text-teal-800"
            >
                {{ role.replaceAll('_', ' ') }}
            </span>
        </div>

        <section v-if="kpis.length" class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiStatCard
                v-for="kpi in kpis"
                :key="kpi.key"
                v-bind="kpi"
            />
        </section>

        <section class="mb-6 grid gap-6 xl:grid-cols-12">
            <div v-if="revenue_chart" class="wh-dash-panel xl:col-span-8">
                <div class="wh-dash-panel-header">
                    <div>
                        <h3>Revenue by source</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ filters.label }}</p>
                    </div>
                    <Link href="/finance/dashboard/operations" class="text-xs font-medium text-teal-700 hover:underline">
                        Operations dashboard
                    </Link>
                </div>
                <RevenueBySourceChart :chart="revenue_chart" />
            </div>

            <div class="wh-dash-panel" :class="revenue_chart ? 'xl:col-span-4' : 'xl:col-span-12'">
                <div class="wh-dash-panel-header">
                    <h3>Quick links</h3>
                    <span class="text-xs text-slate-500">Modules for your role</span>
                </div>
                <div class="p-5">
                    <QuickLinksGrid :links="quick_links" />
                </div>
            </div>
        </section>

        <section class="mb-6 grid gap-6 lg:grid-cols-12">
            <div class="wh-dash-panel lg:col-span-4">
                <div class="wh-dash-panel-header">
                    <h3>Pending approvals</h3>
                </div>
                <div class="px-5 pb-5">
                    <ApprovalList :items="approvals" />
                </div>
            </div>

            <div v-if="occupancy" class="wh-dash-panel lg:col-span-4">
                <div class="wh-dash-panel-header">
                    <h3>Room occupancy</h3>
                    <Link href="/front-desk/rooms" class="text-xs font-medium text-teal-700 hover:underline">View rooms</Link>
                </div>
                <div class="flex items-center gap-6 p-5">
                    <div
                        class="relative flex h-28 w-28 shrink-0 items-center justify-center rounded-full"
                        style="background: conic-gradient(#0f766e 0% calc(var(--pct) * 1%), #e2e8f0 calc(var(--pct) * 1%) 100%)"
                        :style="{ '--pct': occupancy.rate }"
                    >
                        <div class="flex h-20 w-20 flex-col items-center justify-center rounded-full bg-white text-center shadow-sm">
                            <span class="text-xl font-bold text-slate-900">{{ occupancy.rate }}%</span>
                            <span class="text-[10px] uppercase tracking-wide text-slate-500">Occupied</span>
                        </div>
                    </div>
                    <dl class="grid flex-1 gap-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Occupied</dt><dd class="font-semibold">{{ occupancy.occupied }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Available</dt><dd class="font-semibold">{{ occupancy.available }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Maintenance</dt><dd class="font-semibold">{{ occupancy.maintenance }}</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2"><dt class="text-slate-500">Total rooms</dt><dd class="font-semibold">{{ occupancy.total }}</dd></div>
                    </dl>
                </div>
            </div>

            <div v-if="attendance" class="wh-dash-panel lg:col-span-4">
                <div class="wh-dash-panel-header">
                    <h3>Attendance today</h3>
                    <Link href="/hr/attendance" class="text-xs font-medium text-teal-700 hover:underline">Open attendance</Link>
                </div>
                <div class="flex items-center gap-6 p-5">
                    <div
                        class="relative flex h-28 w-28 shrink-0 items-center justify-center rounded-full"
                        style="background: conic-gradient(#059669 0% calc(var(--pct) * 1%), #fecaca calc(var(--pct) * 1%) 100%)"
                        :style="{ '--pct': attendance.total ? (attendance.present / attendance.total) * 100 : 0 }"
                    >
                        <div class="flex h-20 w-20 flex-col items-center justify-center rounded-full bg-white text-center shadow-sm">
                            <span class="text-xl font-bold text-slate-900">{{ attendance.present }}</span>
                            <span class="text-[10px] uppercase tracking-wide text-slate-500">Present</span>
                        </div>
                    </div>
                    <dl class="grid flex-1 gap-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Present</dt><dd class="font-semibold text-emerald-700">{{ attendance.present }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Absent</dt><dd class="font-semibold text-red-700">{{ attendance.absent }}</dd></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2"><dt class="text-slate-500">Recorded</dt><dd class="font-semibold">{{ attendance.total }}</dd></div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="mb-6">
            <div class="wh-dash-panel">
                <div class="wh-dash-panel-header">
                    <h3>Notice board</h3>
                </div>
                <div class="divide-y divide-slate-100 px-5 pb-2 lg:grid lg:grid-cols-2 lg:gap-4 lg:divide-y-0">
                    <div v-for="(notice, index) in notices" :key="index" class="py-4">
                        <p class="text-sm font-semibold text-slate-900">{{ notice.title }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ notice.body }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="secondary_kpis.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiStatCard
                v-for="kpi in secondary_kpis"
                :key="kpi.key"
                v-bind="kpi"
            />
        </section>
    </AppLayout>
</template>
