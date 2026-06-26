<script setup>
import { Link } from '@inertiajs/vue3';
import DataTable from '../../../Components/DataTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    goodsReceipt: { type: Object, required: true },
});

const lineColumns = [
    { key: 'inventory_item', label: 'Item' },
    { key: 'quantity_received', label: 'Qty received', class: 'text-right' },
    { key: 'unit_cost', label: 'Unit cost', class: 'text-right' },
];

function formatMoney(value) {
    const amount = Number.parseFloat(value ?? 0);
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function itemLabel(line) {
    const item = line.inventory_item;
    if (!item) {
        return `Item #${line.inventory_item_id}`;
    }

    return `${item.sku ?? ''} — ${item.name ?? ''}`.trim();
}
</script>

<template>
    <AppLayout :title="`GR #${goodsReceipt.id}`">
        <PageHeader
            :title="`Goods receipt #${goodsReceipt.id}`"
            :subtitle="`PO #${goodsReceipt.purchase_order_id ?? goodsReceipt.purchase_order?.id ?? '—'} · ${goodsReceipt.received_at ?? goodsReceipt.created_at ?? ''}`"
        >
            <template #actions>
                <Link
                    v-if="goodsReceipt.purchase_order_id"
                    :href="`/inventory/purchase-orders/${goodsReceipt.purchase_order_id}`"
                    class="wh-btn-secondary text-xs"
                >
                    View PO
                </Link>
                <Link href="/inventory/purchase-orders" class="wh-btn-secondary text-xs">All POs</Link>
            </template>
        </PageHeader>

        <section class="wh-card p-4">
            <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Received lines</h3>
            <DataTable :columns="lineColumns" :rows="goodsReceipt.lines ?? []" empty-message="No receipt lines.">
                <template #cell-inventory_item="{ row }">
                    {{ itemLabel(row) }}
                </template>
                <template #cell-unit_cost="{ row }">
                    <span class="wh-money">{{ formatMoney(row.unit_cost) }}</span>
                </template>
            </DataTable>
        </section>
    </AppLayout>
</template>
