<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    emptyMessage: {
        type: String,
        default: 'No records found.',
    },
});
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="wh-table">
            <thead>
                <tr>
                    <th v-for="column in columns" :key="column.key" :class="column.class">
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="rows.length === 0">
                    <td :colspan="columns.length" class="py-8 text-center text-slate-500">
                        {{ emptyMessage }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in rows"
                    :key="row.id ?? index"
                    class="border-t border-slate-100 hover:bg-slate-50/80"
                >
                    <td v-for="column in columns" :key="column.key" :class="column.class">
                        <slot :name="`cell-${column.key}`" :row="row">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
