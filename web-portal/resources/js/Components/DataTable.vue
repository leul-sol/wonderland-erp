<script setup>
import { ArrowUpDown, CalendarRange, ChevronDown, Filter, Search } from 'lucide-vue-next';
import { computed, ref, useSlots } from 'vue';
import EmptyState from './EmptyState.vue';
import Pagination from './Pagination.vue';

const props = defineProps({
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
    emptyTitle: {
        type: String,
        default: '',
    },
    emptyDescription: {
        type: String,
        default: 'Records will appear here once they are created.',
    },
    emptyVariant: {
        type: String,
        default: 'table',
    },
    listTitle: {
        type: String,
        default: '',
    },
    search: {
        type: String,
        default: '',
    },
    searchPlaceholder: {
        type: String,
        default: 'Search',
    },
    searchable: {
        type: Boolean,
        default: false,
    },
    selectable: {
        type: Boolean,
        default: false,
    },
    rowKey: {
        type: String,
        default: 'id',
    },
    meta: {
        type: Object,
        default: null,
    },
    perPage: {
        type: [Number, String],
        default: 10,
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100],
    },
    sortBy: {
        type: String,
        default: '',
    },
    sortOptions: {
        type: Array,
        default: () => [],
    },
    dateRangeLabel: {
        type: String,
        default: '',
    },
    showFilterButton: {
        type: Boolean,
        default: true,
    },
    showSortButton: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['search', 'page', 'row-click', 'sort', 'per-page', 'filter-toggle']);

const slots = useSlots();
const selected = ref(new Set());
const filtersOpen = ref(false);
const allSelected = computed(() => props.rows.length > 0 && selected.value.size === props.rows.length);

const isListLayout = computed(() => Boolean(props.listTitle));
const showSearch = computed(() => props.searchable || isListLayout.value);

const showHeaderRow = computed(() => Boolean(
    props.listTitle
    || props.dateRangeLabel
    || slots.toolbar
    || slots.filters
    || props.sortOptions.length,
));

const showControlsRow = computed(() => isListLayout.value || props.searchable || props.meta);

const activePerPage = computed(() => String(props.meta?.per_page ?? props.perPage));

const resolvedEmptyTitle = computed(() => props.emptyTitle || props.emptyMessage);

const resolvedEmptyDescription = computed(() => {
    if (props.emptyTitle) {
        return props.emptyMessage;
    }

    return props.emptyDescription;
});

function resolveRowKey(row, index) {
    return row?.[props.rowKey] ?? index;
}

function toggleAll(event) {
    if (event.target.checked) {
        selected.value = new Set(props.rows.map((row, index) => resolveRowKey(row, index)));
        return;
    }
    selected.value = new Set();
}

function toggleRow(row, index) {
    const key = resolveRowKey(row, index);
    const next = new Set(selected.value);
    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }
    selected.value = next;
}

function onSearchInput(event) {
    emit('search', event.target.value);
}

function onPerPageChange(event) {
    emit('per-page', Number.parseInt(event.target.value, 10));
}

function onSortChange(event) {
    emit('sort', event.target.value);
}

function toggleFilters() {
    filtersOpen.value = !filtersOpen.value;
    emit('filter-toggle', filtersOpen.value);
}

function onColumnSort(column) {
    if (!column.sortable) {
        return;
    }
    emit('sort', column.key);
}

function onRowClick(row) {
    emit('row-click', row);
}
</script>

<template>
    <div class="wh-table-card">
        <div v-if="showHeaderRow" class="wh-table-header">
            <h3 v-if="listTitle" class="wh-table-toolbar-title">{{ listTitle }}</h3>
            <div v-else class="hidden sm:block" />

            <div class="wh-table-toolbar-actions">
                <slot name="toolbar" />

                <button
                    v-if="dateRangeLabel"
                    type="button"
                    class="wh-table-control-btn"
                >
                    <CalendarRange class="h-4 w-4 text-slate-500" />
                    <span class="hidden md:inline">{{ dateRangeLabel }}</span>
                    <ChevronDown class="h-4 w-4 text-slate-400" />
                </button>

                <button
                    v-if="showFilterButton && $slots.filters"
                    type="button"
                    class="wh-table-control-btn"
                    :class="filtersOpen ? 'wh-table-control-btn-active' : ''"
                    @click="toggleFilters"
                >
                    <Filter class="h-4 w-4 text-slate-500" />
                    Filter
                </button>

                <div v-if="showSortButton && sortOptions.length" class="relative">
                    <select
                        class="wh-table-control-btn wh-table-control-select"
                        :value="sortBy"
                        @change="onSortChange"
                    >
                        <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                            {{ option.label }}
                        </option>
                    </select>
                    <ArrowUpDown class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                </div>
            </div>
        </div>

        <div v-if="showControlsRow" class="wh-table-controls">
            <div class="wh-table-controls-left">
                <span class="wh-table-controls-label">Row Per Page</span>
                <div class="relative">
                    <select
                        class="wh-table-per-page"
                        :value="activePerPage"
                        @change="onPerPageChange"
                    >
                        <option v-for="option in perPageOptions" :key="option" :value="option">
                            {{ option }}
                        </option>
                    </select>
                    <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                </div>
                <span class="wh-table-controls-label">Entries</span>
            </div>

            <div v-if="showSearch" class="relative w-full sm:w-auto">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    class="wh-table-search"
                    :value="search"
                    :placeholder="searchPlaceholder"
                    @change="onSearchInput"
                />
            </div>
        </div>

        <div v-if="$slots.filters && filtersOpen" class="wh-table-filters">
            <slot name="filters" />
        </div>

        <div class="overflow-x-auto">
            <table class="wh-table">
                <thead>
                    <tr>
                        <th v-if="selectable" class="w-12">
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                :checked="allSelected"
                                @change="toggleAll"
                            />
                        </th>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :class="[column.class, column.sortable ? 'wh-table-th-sortable' : '']"
                            @click="onColumnSort(column)"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                {{ column.label }}
                                <ArrowUpDown
                                    v-if="column.sortable"
                                    class="h-3.5 w-3.5 text-slate-400"
                                />
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + (selectable ? 1 : 0)" class="p-0">
                            <slot name="empty">
                                <EmptyState
                                    :title="resolvedEmptyTitle"
                                    :description="resolvedEmptyDescription"
                                    :variant="emptyVariant"
                                    compact
                                />
                            </slot>
                        </td>
                    </tr>
                    <tr
                        v-for="(row, index) in rows"
                        :key="resolveRowKey(row, index)"
                        @click="onRowClick(row)"
                    >
                        <td v-if="selectable" @click.stop>
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                :checked="selected.has(resolveRowKey(row, index))"
                                @change="toggleRow(row, index)"
                            />
                        </td>
                        <td v-for="column in columns" :key="column.key" :class="column.class">
                            <slot :name="`cell-${column.key}`" :row="row">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta || $slots.footer" class="wh-table-footer">
            <slot name="footer">
                <Pagination v-if="meta" :meta="meta" @page="emit('page', $event)" />
            </slot>
        </div>
    </div>
</template>
