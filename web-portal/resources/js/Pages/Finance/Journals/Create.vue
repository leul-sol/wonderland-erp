<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
    defaultEntryDate: { type: String, required: true },
});

const form = useForm({
    description: '',
    entry_date: props.defaultEntryDate,
    source_reference: '',
    lines: [
        { account_code: props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' },
        { account_code: props.accounts[1]?.code ?? props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' },
    ],
});

function addLine() {
    form.lines.push({ account_code: props.accounts[0]?.code ?? '', debit: '', credit: '', description: '' });
}

function removeLine(index) {
    if (form.lines.length > 2) {
        form.lines.splice(index, 1);
    }
}

function submit() {
    form.post('/finance/journals');
}
</script>

<template>
    <AppLayout title="New journal">
        <PageHeader title="New manual journal" subtitle="Saved as draft until finance approval">
            <template #actions>
                <Link href="/finance/journals" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-4xl p-6" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
                    <input v-model="form.description" type="text" required class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Entry date</label>
                    <input v-model="form.entry_date" type="date" class="wh-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Reference</label>
                    <input v-model="form.source_reference" type="text" class="wh-input" />
                </div>
            </div>

            <div class="mt-6">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Lines</h3>
                <div class="space-y-3">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="grid gap-3 rounded-lg border border-slate-200 p-3 lg:grid-cols-4"
                    >
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Account</label>
                            <select v-model="line.account_code" required class="wh-input">
                                <option v-for="account in accounts" :key="account.id" :value="account.code">
                                    {{ account.code }} — {{ account.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Debit</label>
                            <input v-model="line.debit" type="number" step="0.01" min="0" class="wh-input" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Credit</label>
                            <input v-model="line.credit" type="number" step="0.01" min="0" class="wh-input" />
                        </div>
                        <div class="flex items-end justify-between gap-2">
                            <input v-model="line.description" type="text" placeholder="Line note" class="wh-input" />
                            <button
                                v-if="form.lines.length > 2"
                                type="button"
                                class="text-xs text-red-600"
                                @click="removeLine(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" class="wh-btn-secondary mt-3" @click="addLine">Add line</button>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save draft</button>
            </div>
        </form>
    </AppLayout>
</template>
