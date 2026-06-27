<script setup>
import FlashToast from './FlashToast.vue';
import { usePageFlash } from '../composables/usePageFlash';

const { error, errorDetail, success } = usePageFlash();
</script>

<template>
    <FlashToast :error="error" :error-detail="errorDetail" :success="success" />
</template>
