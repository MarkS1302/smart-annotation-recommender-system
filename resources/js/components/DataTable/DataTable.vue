<script setup lang="ts" generic="TData extends Record<string, unknown>">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from '@lucide/vue';
import { Checkbox } from '@/components/ui/checkbox';
import { debounce, get, isEmpty } from 'lodash';
import { computed, defineComponent, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { DataTableAction, DataTableColumn } from '@/composables/useDataTableColumn';
import type { ResourceCollection } from '@/types/response/resource-collection';

type Props<TRecord extends Record<string, unknown>> = {
    columns: DataTableColumn<TRecord>[];
    resourceCollection: ResourceCollection<TRecord>;
    title?: string;
    description?: string;
    searchableAttribute?: string;
    initialSearch?: string;
    preserveState?: boolean;
    preserveScroll?: boolean;
    propsToReload?: string[];
    hidePagination?: boolean;
    hidePaginationInfo?: boolean;
    pageSizes?: number[];
};

const {
    columns,
    resourceCollection,
    title,
    description,
    searchableAttribute,
    initialSearch = '',
    preserveState = true,
    preserveScroll = false,
    propsToReload = [],
    hidePagination = false,
    hidePaginationInfo = false,
    pageSizes = [15, 30, 50],
} = defineProps<Props<TData>>();

const searchValue = ref(initialSearch);
const selectedRowIds = ref<Array<string | number>>([]);

const CellRenderer = defineComponent({
    name: 'DataTableCellRenderer',
    props: {
        value: {
            type: null,
            default: '',
        },
    },
    setup(props) {
        return () => props.value as never;
    },
});

function getRowId(row: TData): string | number {
    const value = get(row, 'id');

    if (typeof value === 'number' || typeof value === 'string') {
        return value;
    }

    return JSON.stringify(row);
}

function isRowSelected(row: TData): boolean {
    return selectedRowIds.value.includes(getRowId(row));
}

function setRowSelected(row: TData, checked: boolean): void {
    const rowId = getRowId(row);

    if (checked) {
        if (!selectedRowIds.value.includes(rowId)) {
            selectedRowIds.value = [...selectedRowIds.value, rowId];
        }

        return;
    }

    selectedRowIds.value = selectedRowIds.value.filter(
        (selectedRowId) => selectedRowId !== rowId,
    );
}

function toggleAllRows(checked: boolean): void {
    if (!checked) {
        selectedRowIds.value = [];

        return;
    }

    selectedRowIds.value = resourceCollection.data.map((row) => getRowId(row));
}

const selectedRows = computed(() =>
    resourceCollection.data.filter((row) => isRowSelected(row)),
);
const isAllRowsSelected = computed(
    () =>
        resourceCollection.data.length > 0 &&
        selectedRows.value.length === resourceCollection.data.length,
);
const isSomeRowsSelected = computed(
    () => selectedRows.value.length > 0 && !isAllRowsSelected.value,
);
function currentFilter(): Record<string, unknown> {
    if (!searchableAttribute) {
        return {};
    }

    return {
        filter: {
            [searchableAttribute]: searchValue.value,
        },
    };
}

function visit(url: string | null | undefined): void {
    if (!url) {
        return;
    }

    router.get(url, currentFilter(), {
        preserveState,
        preserveScroll,
        ...(propsToReload.length ? { only: propsToReload } : {}),
    });
}

watch(
    searchValue,
    debounce((value: string) => {
        if (!searchableAttribute) {
            return;
        }

        router.get(
            resourceCollection.meta.path,
            {
                filter: {
                    [searchableAttribute]: value,
                },
                pageSize: resourceCollection.meta.per_page,
            },
            {
                preserveState,
                preserveScroll,
                replace: true,
                ...(propsToReload.length ? { only: propsToReload } : {}),
            },
        );
    }, 300),
);

watch(
    () => resourceCollection.data,
    () => {
        selectedRowIds.value = selectedRowIds.value.filter((selectedRowId) =>
            resourceCollection.data.some(
                (row) => getRowId(row) === selectedRowId,
            ),
        );
    },
);

function setPageSize(pageSize: string): void {
    const url = new URL(window.location.href);
    url.searchParams.set('pageSize', pageSize);

    if (searchableAttribute) {
        url.searchParams.set(
            `filter[${searchableAttribute}]`,
            searchValue.value,
        );
    }

    router.get(
        `${url.pathname}${url.search}${url.hash}`,
        {},
        {
            preserveState,
            preserveScroll,
            replace: true,
            ...(propsToReload.length ? { only: propsToReload } : {}),
        },
    );
}

const canPreviousPage = computed(() => !isEmpty(resourceCollection.links.prev));
const canNextPage = computed(() => !isEmpty(resourceCollection.links.next));
const pageInfo = computed(() => ({
    current: resourceCollection.meta.current_page,
    total: resourceCollection.meta.last_page,
}));

function formatCell(value: unknown): string {
    if (value === null || value === undefined) {
        return '';
    }

    if (Array.isArray(value)) {
        return value.map((item) => formatCell(item)).join(', ');
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function resolveActionRoute<TRecord extends Record<string, unknown>>(
    action: DataTableAction<TRecord>,
    row: TRecord,
): string | undefined {
    if (typeof action.actionRoute === 'function') {
        return action.actionRoute(row);
    }

    return action.actionRoute;
}

function resolveActionProps<TRecord extends Record<string, unknown>>(
    action: DataTableAction<TRecord>,
    row: TRecord,
): Record<string, unknown> {
    const props =
        typeof action.props === 'function'
            ? action.props(row)
            : (action.props ?? {});

    return {
        ...props,
        ...(resolveActionRoute(action, row)
            ? { actionRoute: resolveActionRoute(action, row) }
            : {}),
        ...(action.onClickHandler
            ? { onClickHandler: () => action.onClickHandler?.(row) }
            : {}),
    };
}

function visibleActions<TRecord extends Record<string, unknown>>(
    actionList: DataTableAction<TRecord>[],
): DataTableAction<TRecord>[] {
    return actionList.filter((action) => action.hasPermission?.() ?? true);
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="title || description" class="space-y-1">
            <h1 v-if="title" class="text-2xl font-semibold tracking-tight">
                {{ title }}
            </h1>
            <p v-if="description" class="text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
            <div v-if="searchableAttribute" class="w-full sm:w-72">
                <Input
                    v-model="searchValue"
                    :placeholder="`Search ${title?.toLowerCase() ?? 'items'}`"
                />
            </div>
            <slot name="filters-toolbar" />
            <slot name="actions" />
        </div>

        <Card>
            <CardHeader v-if="$slots['bulk-actions']" class="pb-0">
                <div class="flex w-full items-center justify-between gap-3">
                    <div class="flex-1">
                        <slot
                            name="bulk-actions"
                            :selected-rows="selectedRows"
                            :selected-row-ids="selectedRowIds"
                        />
                    </div>
                    <div class="flex-1" />
                </div>
            </CardHeader>

            <CardContent class="overflow-x-auto px-0">
                <table class="w-full caption-bottom text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th
                                v-for="column in columns"
                                :key="String(column.key)"
                                scope="col"
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                :class="column.headerClass"
                            >
                                <template v-if="column.selectable">
                                    <Checkbox
                                        :checked="
                                            isAllRowsSelected
                                                ? true
                                                : isSomeRowsSelected
                                                  ? 'indeterminate'
                                                  : false
                                        "
                                        @update:checked="
                                            (checked: boolean) =>
                                                toggleAllRows(Boolean(checked))
                                        "
                                    />
                                </template>
                                <template v-else>
                                    {{ column.title }}
                                </template>
                            </th>
                        </tr>
                    </thead>

                    <tbody v-if="resourceCollection.data.length">
                        <tr
                            v-for="row in resourceCollection.data"
                            :key="String(getRowId(row))"
                            class="border-b transition-colors hover:bg-muted/30"
                        >
                            <td
                                v-for="column in columns"
                                :key="String(column.key)"
                                class="p-4 align-middle"
                                :class="column.class"
                            >
                                <template v-if="column.selectable">
                                    <Checkbox
                                        :checked="isRowSelected(row)"
                                        @update:checked="
                                            (checked: boolean) =>
                                                setRowSelected(
                                                    row,
                                                    Boolean(checked),
                                                )
                                        "
                                    />
                                </template>
                                <template v-else-if="column.actions?.length">
                                    <div
                                        class="flex flex-wrap justify-end gap-2"
                                    >
                                        <component
                                            :is="action.component"
                                            v-for="(
                                                action, actionIndex
                                            ) in visibleActions(column.actions)"
                                            :key="`${String(column.key)}-${actionIndex}`"
                                            v-bind="
                                                resolveActionProps(action, row)
                                            "
                                        />
                                    </div>
                                </template>
                                <template v-else>
                                    <slot
                                        :name="`cell-${String(column.key)}`"
                                        :row="row"
                                        :value="get(row, column.key)"
                                        :column="column"
                                    >
                                        <CellRenderer
                                            :value="
                                                column.cellFormatter
                                                    ? column.cellFormatter(
                                                          row,
                                                          get(row, column.key),
                                                      )
                                                    : formatCell(
                                                          get(row, column.key),
                                                      )
                                            "
                                        />
                                    </slot>
                                </template>
                            </td>
                        </tr>
                    </tbody>

                    <tbody v-else>
                        <tr>
                            <td
                                :colspan="columns.length"
                                class="h-24 px-4 text-center text-muted-foreground"
                            >
                                No results found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>

            <CardFooter v-if="!hidePagination">
                <div
                    :class="`flex w-full items-center ${resourceCollection.meta.total > 0 ? 'justify-between' : 'justify-end'} gap-3`"
                >
                    <div
                        v-if="resourceCollection.meta.total > 0"
                        class="text-sm font-medium text-muted-foreground"
                    >
                        Showing {{ resourceCollection.meta.from }} to
                        {{ resourceCollection.meta.to }} of
                        {{ resourceCollection.meta.total }}
                    </div>

                    <div class="flex items-center gap-3">
                        <Select
                            :model-value="`${resourceCollection.meta.per_page}`"
                            @update:model-value="setPageSize"
                        >
                            <SelectTrigger class="h-8 w-[84px]">
                                <SelectValue
                                    :placeholder="`${resourceCollection.meta.per_page}`"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="pageSize in pageSizes"
                                    :key="pageSize"
                                    :value="`${pageSize}`"
                                >
                                    {{ pageSize }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <div
                            v-if="!hidePaginationInfo"
                            class="hidden text-sm font-medium lg:block"
                        >
                            Page {{ pageInfo.current }} of {{ pageInfo.total }}
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                :disabled="!canPreviousPage"
                                class="h-8 w-8 p-0"
                                variant="outline"
                                @click="visit(resourceCollection.links.first)"
                            >
                                <span class="sr-only">First page</span>
                                <ChevronsLeft class="h-4 w-4" />
                            </Button>

                            <Button
                                :disabled="!canPreviousPage"
                                class="h-8 w-8 p-0"
                                variant="outline"
                                @click="visit(resourceCollection.links.prev)"
                            >
                                <span class="sr-only">Previous page</span>
                                <ChevronLeft class="h-4 w-4" />
                            </Button>

                            <Button
                                :disabled="!canNextPage"
                                class="h-8 w-8 p-0"
                                variant="outline"
                                @click="visit(resourceCollection.links.next)"
                            >
                                <span class="sr-only">Next page</span>
                                <ChevronRight class="h-4 w-4" />
                            </Button>

                            <Button
                                :disabled="!canNextPage"
                                class="h-8 w-8 p-0"
                                variant="outline"
                                @click="visit(resourceCollection.links.last)"
                            >
                                <span class="sr-only">Last page</span>
                                <ChevronsRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardFooter>
        </Card>
    </div>
</template>
