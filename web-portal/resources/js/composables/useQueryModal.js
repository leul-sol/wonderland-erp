import { onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Open a modal when the URL contains ?open=create (or custom values).
 */
export function useQueryModal(modalOpen, options = {}) {
    const { param = 'open', expected = 'create', onOpen = null, when = true } = options;

    onMounted(() => {
        if (when === false) {
            return;
        }

        if (typeof when === 'function' && !when()) {
            return;
        }

        const page = usePage();
        const rawUrl = page.url ?? '';
        const [path, query = ''] = rawUrl.split('?');
        const params = new URLSearchParams(query);

        if (params.get(param) !== expected) {
            return;
        }

        modalOpen.value = true;

        if (typeof onOpen === 'function') {
            onOpen(params);
        }

        params.delete(param);
        const remaining = params.toString();
        const nextUrl = remaining ? `${path}?${remaining}` : path;

        if (nextUrl !== rawUrl) {
            router.replace(nextUrl, { preserveState: true, preserveScroll: true });
        }
    });
}
