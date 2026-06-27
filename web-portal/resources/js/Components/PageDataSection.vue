<script setup>
import { Deferred } from '@inertiajs/vue3';
import PageDataLoading from './PageDataLoading.vue';

defineProps({
    keys: {
        type: [String, Array],
        required: true,
    },
    label: {
        type: String,
        default: 'Loading data…',
    },
});
</script>

<template>
    <Deferred :data="keys">
        <template #fallback>
            <PageDataLoading :label="label" />
        </template>
        <slot />
    </Deferred>
</template>
