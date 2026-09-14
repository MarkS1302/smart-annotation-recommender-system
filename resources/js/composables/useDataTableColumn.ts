import type { Component } from 'vue';

export type DataTableCellFormatter<TData> = (
    row: TData,
    value: unknown,
) => unknown;

export type DataTableAction<TData> = {
    component: Component;
    actionRoute?: string | ((row: TData) => string);
    hasPermission?: () => boolean;
    onClickHandler?: (row: TData) => void;
    props?: Record<string, unknown> | ((row: TData) => Record<string, unknown>);
};

export type DataTableColumn<TData> = {
    key: keyof TData | string;
    title: string;
    class?: string;
    headerClass?: string;
    size?: number;
    sortable?: boolean;
    cellFormatter?: DataTableCellFormatter<TData>;
    actions?: DataTableAction<TData>[];
    selectable?: boolean;
};

export type DataTableOptions = {
    selectable?: boolean;
};

export function useDataTableColumn<TData extends Record<string, unknown>>(
    columns: DataTableColumn<TData>[],
    actions: DataTableAction<TData>[] = [],
    options: DataTableOptions = {},
): DataTableColumn<TData>[] {
    const normalizedColumns = [...columns];

    if (options.selectable) {
        normalizedColumns.unshift({
            key: 'select',
            title: '',
            class: 'w-12',
            headerClass: 'w-12',
            selectable: true,
        });
    }

    if (actions.length > 0) {
        normalizedColumns.push({
            key: 'actions',
            title: 'Actions',
            class: 'whitespace-nowrap',
            headerClass: 'text-right',
            actions,
        });
    }

    return normalizedColumns;
}
