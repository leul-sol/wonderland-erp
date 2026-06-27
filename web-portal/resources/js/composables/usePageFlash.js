import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePageFlash() {
    const page = usePage();

    const error = computed(() => page.props.flash?.error ?? null);
    const errorDetail = computed(() => page.props.flash?.error_detail ?? null);
    const success = computed(() => page.props.flash?.success ?? null);

    return {
        error,
        errorDetail,
        success,
    };
}
