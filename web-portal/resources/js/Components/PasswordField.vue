<script setup>
import { Eye, EyeOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    modelValue: { type: String, default: '' },
    required: { type: Boolean, default: false },
    minlength: { type: [Number, String], default: undefined },
    autocomplete: { type: String, default: 'new-password' },
    invalid: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const showPassword = ref(false);

const inputClass = computed(() => [
    'wh-input pr-10',
    props.invalid ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : '',
]);
</script>

<template>
    <div class="relative">
        <input
            :id="id"
            :value="modelValue"
            :type="showPassword ? 'text' : 'password'"
            :required="required"
            :minlength="minlength"
            :autocomplete="autocomplete"
            :class="inputClass"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <button
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            @click="showPassword = !showPassword"
        >
            <EyeOff v-if="showPassword" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
        </button>
    </div>
</template>
