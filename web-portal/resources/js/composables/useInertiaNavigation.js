import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const pendingPath = ref(null);
const isNavigating = ref(false);
let activeGetVisits = 0;
let subscribed = false;

export function normalizePath(href) {
    if (!href) {
        return '';
    }

    const raw = String(href).split('?')[0];

    if (raw.startsWith('http://') || raw.startsWith('https://')) {
        try {
            return new URL(raw).pathname.replace(/\/+$/, '') || '/';
        } catch {
            return raw;
        }
    }

    const path = raw.startsWith('/') ? raw : `/${raw}`;

    return path.replace(/\/+$/, '') || '/';
}

function subscribeOnce() {
    if (subscribed) {
        return;
    }

    subscribed = true;

    router.on('start', (event) => {
        const visit = event.detail.visit;

        if (visit.method !== 'get') {
            return;
        }

        activeGetVisits += 1;
        isNavigating.value = true;

        if (!visit.deferredProps) {
            pendingPath.value = normalizePath(visit.url);
        }
    });

    router.on('finish', (event) => {
        const visit = event.detail.visit;

        if (visit.method !== 'get') {
            return;
        }

        activeGetVisits = Math.max(0, activeGetVisits - 1);
        isNavigating.value = activeGetVisits > 0;

        if (!isNavigating.value) {
            pendingPath.value = null;
        }
    });

    router.on('cancel', (event) => {
        const visit = event.detail.visit;

        if (visit?.method !== 'get') {
            return;
        }

        activeGetVisits = Math.max(0, activeGetVisits - 1);
        isNavigating.value = activeGetVisits > 0;

        if (!isNavigating.value) {
            pendingPath.value = null;
        }
    });

    router.on('error', (event) => {
        const visit = event.detail.visit;

        if (visit?.method !== 'get') {
            return;
        }

        activeGetVisits = Math.max(0, activeGetVisits - 1);
        isNavigating.value = activeGetVisits > 0;

        if (!isNavigating.value) {
            pendingPath.value = null;
        }
    });
}

export function markPendingNavigation(href) {
    subscribeOnce();
    pendingPath.value = normalizePath(href);
}

export function useInertiaNavigation() {
    subscribeOnce();

    return {
        pendingPath,
        isNavigating,
        markPendingNavigation,
    };
}
