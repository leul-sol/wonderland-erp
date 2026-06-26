import { reactive } from 'vue';

const state = reactive({
    open: false,
    title: 'Confirm',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'default',
    prompt: false,
    promptLabel: '',
    promptPlaceholder: '',
    promptValue: '',
    _resolve: null,
});

function resetPrompt() {
    state.prompt = false;
    state.promptLabel = '';
    state.promptPlaceholder = '';
    state.promptValue = '';
}

/**
 * @param {{
 *   title?: string,
 *   message?: string,
 *   confirmLabel?: string,
 *   cancelLabel?: string,
 *   variant?: 'default' | 'danger',
 *   prompt?: boolean,
 *   promptLabel?: string,
 *   promptPlaceholder?: string,
 *   promptValue?: string,
 * }} options
 * @returns {Promise<string|boolean>}
 */
export function confirmAction(options) {
    return new Promise((resolve) => {
        resetPrompt();

        state.title = options.title ?? 'Confirm';
        state.message = options.message ?? '';
        state.confirmLabel = options.confirmLabel ?? 'Confirm';
        state.cancelLabel = options.cancelLabel ?? 'Cancel';
        state.variant = options.variant ?? 'default';
        state.prompt = options.prompt ?? false;
        state.promptLabel = options.promptLabel ?? '';
        state.promptPlaceholder = options.promptPlaceholder ?? '';
        state.promptValue = options.promptValue ?? '';
        state._resolve = resolve;
        state.open = true;
    });
}

export function acceptConfirm() {
    const resolve = state._resolve;
    const value = state.prompt ? state.promptValue : true;

    state.open = false;
    state._resolve = null;
    resetPrompt();
    resolve?.(value);
}

export function cancelConfirm() {
    const resolve = state._resolve;

    state.open = false;
    state._resolve = null;
    resetPrompt();
    resolve?.(false);
}

export function useConfirm() {
    return {
        state,
        confirm: confirmAction,
        accept: acceptConfirm,
        cancel: cancelConfirm,
    };
}
