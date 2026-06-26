<script setup>
import ConfirmModal from '../Components/ConfirmModal.vue';
import SidebarNav from '../Components/SidebarNav.vue';
import TopBar from '../Components/TopBar.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const flashError = computed(() => page.props.flash?.error ?? null);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const mobileNavOpen = ref(false);
const sidebarCollapsed = ref(false);

const sidebarWidth = computed(() => (sidebarCollapsed.value ? '80px' : '260px'));

// Default expanded so first visit shows full navigation (user can collapse via top bar).
onMounted(() => {
    const stored = localStorage.getItem('wh-sidebar-collapsed');
    sidebarCollapsed.value = stored === '1';
});

watch(sidebarCollapsed, (value) => {
    localStorage.setItem('wh-sidebar-collapsed', value ? '1' : '0');
});

function toggleSidebarCollapse() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
}

function handleSidebarToggle() {
    if (window.matchMedia('(max-width: 1023px)').matches) {
        mobileNavOpen.value = !mobileNavOpen.value;
        return;
    }

    toggleSidebarCollapse();
}
</script>

<template>
    <div class="min-h-screen bg-[#f8fafc]">
        <ConfirmModal />

        <div v-if="flashError || flashSuccess" class="fixed right-4 top-4 z-50 max-w-sm">
            <div
                v-if="flashError"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-lg"
            >
                {{ flashError }}
            </div>
            <div
                v-if="flashSuccess"
                class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg"
            >
                {{ flashSuccess }}
            </div>
        </div>

        <div
            v-if="mobileNavOpen"
            class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"
            @click="mobileNavOpen = false"
        />

        <div
            class="min-h-screen lg:grid"
            :style="{ '--wh-sidebar-width': sidebarWidth }"
        >
            <aside
                class="wh-layout-sidebar fixed inset-y-0 left-0 z-50 flex h-screen w-[260px] flex-col border-r border-slate-200 bg-white transition-[width,transform] duration-200 max-lg:-translate-x-full"
                :class="[
                    mobileNavOpen ? 'max-lg:translate-x-0' : '',
                    sidebarCollapsed ? 'lg:w-20' : 'lg:w-[260px]',
                ]"
            >
                <SidebarNav
                    class="min-h-0 flex-1"
                    :collapsed="sidebarCollapsed"
                    :mobile-open="mobileNavOpen"
                    @close-mobile="mobileNavOpen = false"
                />
            </aside>

            <div class="flex min-h-screen min-w-0 flex-col max-lg:min-h-screen lg:col-start-2">
                <TopBar
                    :sidebar-collapsed="sidebarCollapsed"
                    :on-toggle-sidebar="handleSidebarToggle"
                />

                <main class="flex-1 px-4 py-6 sm:px-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media (min-width: 1024px) {
    .wh-layout-sidebar {
        position: sticky;
        top: 0;
        z-index: 30;
        height: 100vh;
        width: var(--wh-sidebar-width);
        transform: translateX(0) !important;
    }

    .min-h-screen.lg\:grid {
        grid-template-columns: var(--wh-sidebar-width) minmax(0, 1fr);
    }
}
</style>
