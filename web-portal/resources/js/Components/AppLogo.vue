<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'full',
        validator: (value) => ['full', 'mark'].includes(value),
    },
    theme: {
        type: String,
        default: 'light',
        validator: (value) => ['light', 'dark'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value),
    },
    showName: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const imageFailed = ref(false);

const brand = computed(() => page.props.brand ?? {});

const logoUrl = computed(() => {
    if (imageFailed.value) {
        return null;
    }

    if (props.variant === 'mark') {
        return brand.value.logo_mark || brand.value.logo || null;
    }

    return brand.value.logo || brand.value.logo_mark || null;
});

const sizeClasses = {
    sm: 'h-8',
    md: 'h-10',
    lg: 'h-16',
    xl: 'h-20 sm:h-24',
};

const markSizeClasses = {
    sm: 'h-8 w-8 text-sm',
    md: 'h-9 w-9 text-base',
    lg: 'h-12 w-12 text-lg',
    xl: 'h-16 w-16 text-xl',
};

const titleClasses = computed(() => {
    if (props.theme === 'dark') {
        return 'text-white';
    }

    return 'text-slate-800';
});

const subtitleClasses = computed(() => {
    if (props.theme === 'dark') {
        return 'text-teal-100/80';
    }

    return 'text-slate-500';
});

watch(logoUrl, () => {
    imageFailed.value = false;
});

function onImageError() {
    imageFailed.value = true;
}
</script>

<template>
    <div class="flex min-w-0 items-center gap-3" :class="variant === 'mark' ? 'justify-center' : ''">
        <img
            v-if="logoUrl"
            :src="logoUrl"
            :alt="brand.name"
            :class="[
                variant === 'mark'
                    ? [markSizeClasses[size], 'object-contain']
                    : [sizeClasses[size], 'w-auto'],
                'shrink-0',
            ]"
            @error="onImageError"
        />
        <div
            v-else
            class="flex shrink-0 items-center justify-center rounded-xl bg-teal-700 font-bold text-white shadow-sm"
            :class="markSizeClasses[size]"
            :title="brand.name"
        >
            {{ (brand.name || 'W').charAt(0) }}
        </div>

        <div v-if="showName && variant === 'full'" class="min-w-0">
            <p class="truncate text-[17px] font-bold tracking-tight" :class="titleClasses">
                {{ brand.name }}
            </p>
            <p v-if="brand.product" class="truncate text-xs font-medium" :class="subtitleClasses">
                {{ brand.product }}
            </p>
        </div>
    </div>
</template>
