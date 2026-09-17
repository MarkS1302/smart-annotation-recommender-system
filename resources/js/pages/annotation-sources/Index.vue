<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, h, ref, watch } from 'vue';
import DownloadAnnotationSourceController from '@/actions/App/Http/Controllers/AnnotationSources/DownloadAnnotationSourceController';
import CreateButton from '@/components/Buttons/CreateButton.vue';
import DeleteAction from '@/components/DataTable/Actions/DeleteAction.vue';
import DownloadSourceAction from '@/components/DataTable/Actions/DownloadSourceAction.vue';
import EditAction from '@/components/DataTable/Actions/EditAction.vue';
import PreviewSourceAction from '@/components/DataTable/Actions/PreviewSourceAction.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Badge } from '@/components/ui/badge';
import type { DataTableAction } from '@/composables/useDataTableColumn';
import { useDataTableColumn } from '@/composables/useDataTableColumn';
import CreateAnnotationSourceSheet from '@/pages/annotation-sources/partials/CreateAnnotationSourceSheet.vue';
import EditAnnotationSourceSheet from '@/pages/annotation-sources/partials/EditAnnotationSourceSheet.vue';
import SqliteSourcePreviewDialog from '@/pages/annotation-sources/partials/SqliteSourcePreviewDialog.vue';
import { destroy, index } from '@/routes/annotation-sources';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { ResourceCollection } from '@/types/response/resource-collection';

type Source = Record<string, unknown> & {
    id: number;
    name: string;
    type: 'json' | 'sqlite';
    tag_column: string | null;
    active: boolean;
    json_content?: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type Props = {
    filters: {
        search: string;
    };
    sources: ResourceCollection<Source>;
};

const props = defineProps<Props>();
const { canCreate, canView, canUpdate, canDelete } = usePermissions();
const openCreateSheet = ref(false);
const editingSource = ref<Source | null>(null);
const editSheetOpen = ref(false);
const previewingSource = ref<Source | null>(null);
const previewDialogOpen = ref(false);

function beginEdit(source: Source): void {
    editingSource.value = source;
    editSheetOpen.value = true;
}

watch(editSheetOpen, (value) => {
    if (!value) {
        editingSource.value = null;
    }
});

function previewSource(source: Source): void {
    previewingSource.value = source;
    previewDialogOpen.value = true;
}

function openCreateSourceSheet(): void {
    openCreateSheet.value = true;
}

function sourceTypeLabel(type: Source['type']): string {
    return type === 'sqlite' ? 'SQLite knowledge base' : 'JSON records';
}

const sourceActions = computed<DataTableAction<Source>[]>(() => {
    const actions: DataTableAction<Source>[] = [
        {
            component: PreviewSourceAction,
            hasPermission: () => canView('annotation-sources'),
            props: (source) => ({
                sourceType: source.type,
                isOpen:
                    previewDialogOpen.value &&
                    previewingSource.value?.id === source.id,
            }),
            onClickHandler: previewSource,
        },
        {
            component: DownloadSourceAction,
            hasPermission: () => canView('annotation-sources'),
            props: (source) => ({
                sourceType: source.type,
            }),
            actionRoute: (source) =>
                DownloadAnnotationSourceController.url(source.id),
        },
    ];

    actions.push({
        component: EditAction,
        hasPermission: () => canUpdate('annotation-sources'),
        onClickHandler: beginEdit,
    });

    actions.push({
        component: DeleteAction,
        hasPermission: () => canDelete('annotation-sources'),
        actionRoute: (source) => destroy.url(source.id),
        props: {
            propsToReload: ['sources'],
            title: 'Delete source?',
            message:
                'This removes the source file and its saved configuration.',
        },
    });

    return actions;
});

const columns = computed(() =>
    useDataTableColumn<Source>(
        [
            {
                key: 'id',
                title: '#',
                class: 'w-16',
            },
            {
                key: 'name',
                title: 'Name',
            },
            {
                key: 'type',
                title: 'Type',
                cellFormatter: (_row, value) =>
                    sourceTypeLabel(value as Source['type']),
            },
            {
                key: 'tag_column',
                title: 'Tag column',
            },
            {
                key: 'active',
                title: 'Status',
                cellFormatter: (_row, value) =>
                    h(
                        Badge,
                        {
                            variant: Boolean(value) ? 'default' : 'secondary',
                        },
                        () => (Boolean(value) ? 'Active' : 'Inactive'),
                    ),
            },
            {
                key: 'created_at',
                title: 'Created',
            },
        ],
        sourceActions.value,
    ),
);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Annotation Sources',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Annotation Sources" />

    <div class="space-y-6">
        <PermissionDeniedState
            v-if="!canView('annotation-sources')"
            resource="annotation sources"
            action="view"
        />

        <template v-else>
            <DataTable
                title="Annotation sources"
                description="Manage files that provide records, tags, and rules for annotation requests."
                :columns="columns"
                :resource-collection="props.sources"
                searchable-attribute="search"
                :initial-search="props.filters.search"
                :props-to-reload="['sources']"
            >
                <template #actions>
                    <CreateButton
                        v-if="canCreate('annotation-sources')"
                        label="Add source"
                        :on-click="openCreateSourceSheet"
                    />
                </template>
            </DataTable>
        </template>
    </div>
    <EditAnnotationSourceSheet
        v-if="canUpdate('annotation-sources') && editingSource"
        v-model:is-open="editSheetOpen"
        :source="editingSource"
    />
    <CreateAnnotationSourceSheet
        v-if="canCreate('annotation-sources')"
        v-model:is-open="openCreateSheet"
    />

    <SqliteSourcePreviewDialog
        v-model:is-open="previewDialogOpen"
        :source="previewingSource"
    />
</template>
