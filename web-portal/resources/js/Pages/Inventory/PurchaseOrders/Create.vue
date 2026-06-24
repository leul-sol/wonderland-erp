<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    inventoryItems: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
});

const form = useForm({
    vendor_name: props.suppliers[0]?.name ?? '',
    lines: [
        {
            inventory_item_id: props.inventoryItems[0]?.id ?? '',
            quantity: 1,
            unit_cost: props.inventoryItems[0]?.unit_cost ?? '',
        },
    ],
});

const lineTotal = computed(() =>
    form.lines.reduce((sum, line) => {
        const qty = Number.parseFloat(line.quantity) || 0;
        const cost = Number.parseFloat(line.unit_cost) || 0;
        return sum + qty * cost;
    }, 0),
);

function addLine() {
    const item = props.inventoryItems[0];
    form.lines.push({
        inventory_item_id: item?.id ?? '',
        quantity: 1,
        unit_cost: item?.unit_cost ?? '',
    });
}

function removeLine(index) {
    if (form.lines.length > 1) {
        form.lines.splice(index, 1);
    }
}

function applySupplier(name) {
    form.vendor_name = name;
}

function onItemChange(line) {
    const item = props.inventoryItems.find((i) => String(i.id) === String(line.inventory_item_id));
    if (item) {
        line.unit_cost = item.unit_cost;
    }
}

function submit() {
    form.post('/inventory/purchase-orders');
}
</script>

<template>
    <AppLayout title="Create purchase order">
        <PageHeader title="Create purchase order" subtitle="Draft PO — submit for tiered approval when ready">
            <template #actions>
                <Link href="/inventory/purchase-orders" class="wh-btn-secondary">Back to list</Link>
            </template>
        </PageHeader>

        <form class="wh-card mx-auto max-w-3xl p-6" @submit.prevent="submit">
            <div class="mb-4">
                <label for="vendor_name" class="mb-1 block text-sm font-medium text-slate-700">Vendor name</label>
                <input id="vendor_name" v-model="form.vendor_name" type="text" required class="wh-input" />
                <div v-if="suppliers.length" class="mt-2 flex flex-wrap gap-2">
                    <button
                        v-for="supplier in suppliers.slice(0, 6)"
                        :key="supplier.id"
                        type="button"
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700 hover:bg-teal-50"
                        @click="applySupplier(supplier.name)"
                    >
                        {{ supplier.name }}
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-for="(line, index) in form.lines"
                    :key="index"
                    class="grid gap-3 rounded-lg border border-slate-200 p-3 sm:grid-cols-4"
                >
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Item</label>
                        <select
                            v-model="line.inventory_item_id"
                            required
                            class="wh-input"
                            @change="onItemChange(line)"
                        >
                            <option value="" disabled>Select item</option>
                            <option v-for="item in inventoryItems" :key="item.id" :value="item.id">
                                {{ item.sku }} — {{ item.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Qty</label>
                        <input v-model="line.quantity" type="number" min="0.001" step="0.001" required class="wh-input" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Unit cost</label>
                        <input v-model="line.unit_cost" type="number" min="0" step="0.01" required class="wh-input" />
                    </div>
                    <div class="sm:col-span-4 flex justify-end">
                        <button
                            v-if="form.lines.length > 1"
                            type="button"
                            class="text-xs text-red-600 hover:underline"
                            @click="removeLine(index)"
                        >
                            Remove line
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                <button type="button" class="wh-btn-secondary" @click="addLine">Add line</button>
                <p class="wh-money text-sm font-semibold">Estimated total ETB {{ lineTotal.toFixed(2) }}</p>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="wh-btn-primary" :disabled="form.processing">Save draft PO</button>
            </div>
        </form>
    </AppLayout>
</template>
