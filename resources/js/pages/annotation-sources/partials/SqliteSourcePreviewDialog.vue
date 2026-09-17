<script setup lang="ts">
import { ref, watch } from 'vue';
import PreviewAnnotationSourceController from '@/actions/App/Http/Controllers/AnnotationSources/PreviewAnnotationSourceController';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Source = {
    id: number;
    name: string;
    tag_column: string | null;
};

type SqlitePreviewTable = {
    name: string;
    columns: string[];
    rows: Record<string, unknown>[];
};

type SqlitePreview = {
    tables: SqlitePreviewTable[];
};

const props = defineProps<{
    source: Source | null;
}>();

const isOpen = defineModel<boolean>('isOpen', { default: false });
const previewData = ref<SqlitePreview | null>(null);
const previewError = ref<string | null>(null);
const previewLoading = ref(false);
let previewRequestVersion = 0;

function formatPreviewValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value) ?? String(value);
    }

    return String(value);
}

function resetPreview(): void {
    previewRequestVersion += 1;
    previewData.value = null;
    previewError.value = null;
    previewLoading.value = false;
}

async function loadPreview(sourceId: number): Promise<void> {
    const requestVersion = ++previewRequestVersion;

    previewData.value = null;
    previewError.value = null;
    previewLoading.value = true;

    try {
        const response = await fetch(
            PreviewAnnotationSourceController.url(sourceId),
            {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );

        if (!response.ok) {
            throw new Error(`Preview failed with status ${response.status}.`);
        }

        const data = (await response.json()) as SqlitePreview;

        if (requestVersion === previewRequestVersion) {
            previewData.value = data;
        }
    } catch (error: unknown) {
        if (requestVersion === previewRequestVersion) {
            previewError.value =
                error instanceof Error
                    ? error.message
                    : 'The SQLite file could not be previewed.';
        }
    } finally {
        if (requestVersion === previewRequestVersion) {
            previewLoading.value = false;
        }
    }
}

watch(
    () => [isOpen.value, props.source?.id] as const,
    ([open]) => {
        if (open && props.source) {
            void loadPreview(props.source.id);

            return;
        }

        resetPreview();
    },
    { immediate: true },
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[85vh] max-w-5xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    SQLite preview<span v-if="source">: {{ source.name }}</span>
                </DialogTitle>
                <DialogDescription>
                    First 20 rows from each table. Tag column:
                    {{ source?.tag_column ?? 'not set' }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <p v-if="previewLoading" class="text-sm text-muted-foreground">
                    Opening SQLite file...
                </p>
                <p v-else-if="previewError" class="text-sm text-destructive">
                    {{ previewError }}
                </p>

                <template v-else-if="previewData">
                    <div
                        v-for="table in previewData.tables"
                        :key="table.name"
                        class="grid gap-2"
                    >
                        <p class="text-sm font-medium">
                            Table: {{ table.name }}
                        </p>
                        <div
                            v-if="table.columns.length"
                            class="overflow-x-auto rounded-md border"
                        >
                            <table class="w-full text-left text-xs">
                                <thead class="border-b bg-muted/50">
                                    <tr>
                                        <th
                                            v-for="column in table.columns"
                                            :key="column"
                                            class="px-3 py-2 font-medium"
                                        >
                                            {{ column }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, rowIndex) in table.rows"
                                        :key="rowIndex"
                                        class="border-b last:border-0"
                                    >
                                        <td
                                            v-for="column in table.columns"
                                            :key="column"
                                            class="max-w-64 px-3 py-2 align-top break-words"
                                        >
                                            {{
                                                formatPreviewValue(row[column])
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No columns found.
                        </p>
                        <p
                            v-if="!table.rows.length"
                            class="text-sm text-muted-foreground"
                        >
                            No rows found.
                        </p>
                    </div>
                    <p
                        v-if="!previewData.tables.length"
                        class="text-sm text-muted-foreground"
                    >
                        No user tables found in this SQLite file.
                    </p>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
