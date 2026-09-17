<script setup lang="ts">
import { Head, useForm, usePoll } from '@inertiajs/vue3';
import type { AcceptableValue } from 'reka-ui';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { index, store } from '@/routes/ai-requests';

type EntityOption = {
    value: string;
    label: string;
    records?: RecordOption[];
};

type RecordOption = {
    value: string;
    label: string;
};

type SourceOption = {
    id: number;
    name: string;
    type: 'json' | 'sqlite';
    tag_column: string | null;
    records: RecordOption[];
};

type RequestResult = {
    status: string;
    entity: string;
    entity_label: string;
    input: Array<{
        role: string;
        content: string;
    }>;
    record_id: string;
    record: Record<string, unknown>;
    answer?: string | null;
    error?: string | null;
    ai_response?: Record<string, unknown> | null;
};

type SelectValue = AcceptableValue | AcceptableValue[] | undefined;

const props = defineProps<{
    defaultPrompt: string;
    entities: EntityOption[];
    jsonSources: SourceOption[];
    knowledgeBases: SourceOption[];
    requestId?: string | null;
    result?: RequestResult | null;
}>();

const firstEntity = props.entities[0] ?? null;
const firstEntityRecord = firstEntity?.records?.[0] ?? null;
const sourceMode = ref<'database' | 'json'>('database');

const form = useForm({
    entity: firstEntity?.value ?? '',
    input_source_id: null as number | null,
    record_id: firstEntityRecord?.value ?? '',
    mode: 'ai',
    prompt: props.defaultPrompt,
    knowledge_base_source_id: null as number | null,
    knowledge_base: null as File | null,
    tag_column: '',
});

const { start, stop } = usePoll(
    2000,
    {
        only: ['result'],
    },
    {
        autoStart: false,
    },
);

const pendingMessages = [
    'AI is stretching before the heavy thinking starts.',
    'Gemma is sniffing the row for useful annotation clues.',
    'Tiny cave robots are sorting fields into meaningful piles.',
    'AI is arguing with itself about the best kebab-case tags.',
    'Model is bonking technical fields away from semantic tags.',
    'Smart rocks are aligning so the annotations make sense.',
];

const progressValue = ref(12);
const messageIndex = ref(0);

const selectedEntity = computed(
    () => props.entities.find((entity) => entity.value === form.entity) ?? null,
);
const selectedJsonSource = computed(
    () =>
        props.jsonSources.find(
            (source) => source.id === form.input_source_id,
        ) ?? null,
);

let progressInterval: ReturnType<typeof setInterval> | null = null;
let messageInterval: ReturnType<typeof setInterval> | null = null;

const currentPendingMessage = computed(
    () => pendingMessages[messageIndex.value],
);

watch(
    () => form.entity,
    () => {
        if (sourceMode.value === 'database') {
            form.record_id = selectedEntity.value?.records?.[0]?.value ?? '';
        }
    },
);

watch(
    () => form.input_source_id,
    () => {
        if (sourceMode.value === 'json') {
            form.record_id =
                selectedJsonSource.value?.records?.[0]?.value ?? '';
        }
    },
);

watch(
    () => form.knowledge_base_source_id,
    (sourceId) => {
        if (sourceId !== null) {
            form.knowledge_base = null;
        }
    },
);

watch(
    () => props.result?.status,
    (status) => {
        if (props.requestId && status === 'pending') {
            start();
            beginPendingAnimation();

            return;
        }

        stopPendingAnimation();
        stop();

        if (status === 'success') {
            progressValue.value = 100;
        }
    },
    {
        immediate: true,
    },
);

onBeforeUnmount(() => {
    stopPendingAnimation();
    stop();
});

function beginPendingAnimation(): void {
    if (progressInterval === null) {
        progressInterval = setInterval(() => {
            progressValue.value = Math.min(
                92,
                progressValue.value + Math.floor(Math.random() * 7) + 3,
            );
        }, 900);
    }

    if (messageInterval === null) {
        messageInterval = setInterval(() => {
            messageIndex.value =
                (messageIndex.value + 1) % pendingMessages.length;
        }, 2400);
    }
}

function stopPendingAnimation(): void {
    if (progressInterval !== null) {
        clearInterval(progressInterval);
        progressInterval = null;
    }

    if (messageInterval !== null) {
        clearInterval(messageInterval);
        messageInterval = null;
    }
}

function submit(): void {
    progressValue.value = 12;
    messageIndex.value = 0;

    form.post(store.url(), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function selectSourceMode(mode: 'database' | 'json'): void {
    sourceMode.value = mode;

    if (mode === 'database') {
        form.input_source_id = null;
        form.entity = firstEntity?.value ?? '';
        form.record_id = selectedEntity.value?.records?.[0]?.value ?? '';

        return;
    }

    form.entity = '';
    form.input_source_id = props.jsonSources[0]?.id ?? null;
    form.record_id = props.jsonSources[0]?.records[0]?.value ?? '';
}

function updateSourceMode(value: SelectValue): void {
    if (value === 'database' || value === 'json') {
        selectSourceMode(value);
    }
}

function updateEntity(value: SelectValue): void {
    form.entity =
        Array.isArray(value) || value === null || value === undefined
            ? ''
            : value.toString();
}

function updateRecord(value: SelectValue): void {
    form.record_id =
        Array.isArray(value) || value === null || value === undefined
            ? ''
            : value.toString();
}

function updateInputSource(value: SelectValue): void {
    form.input_source_id =
        Array.isArray(value) ||
        value === null ||
        value === undefined ||
        value === ''
            ? null
            : Number(value);
}

function updateKnowledgeBaseSource(value: SelectValue): void {
    form.knowledge_base_source_id =
        Array.isArray(value) ||
        value === 'temporary' ||
        value === null ||
        value === undefined ||
        value === ''
            ? null
            : Number(value);
}

function selectKnowledgeBase(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.knowledge_base = input.files?.[0] ?? null;
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'AI requests',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="AI requests" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <Heading
            title="AI requests"
            description="Pick a real project model, submit the request, and wait for the background AI job to finish."
        />

        <Card>
            <CardHeader>
                <CardTitle>New request</CardTitle>
                <CardDescription>
                    The form queues the AI request and polls for the result
                    automatically.
                </CardDescription>
            </CardHeader>

            <CardContent>
                <form class="flex flex-col gap-6" @submit.prevent="submit">
                    <FieldGroup class="gap-4 rounded-lg border p-4">
                        <Field class="gap-1">
                            <FieldLabel>Record input</FieldLabel>
                            <FieldDescription>
                                Choose a database record or a saved JSON record.
                            </FieldDescription>
                        </Field>

                        <Field>
                            <FieldLabel>Source type</FieldLabel>
                            <ToggleGroup
                                type="single"
                                variant="outline"
                                :model-value="sourceMode"
                                class="grid w-full grid-cols-2"
                                @update:model-value="updateSourceMode"
                            >
                                <ToggleGroupItem
                                    value="database"
                                    class="w-full"
                                >
                                    Application database
                                </ToggleGroupItem>
                                <ToggleGroupItem
                                    value="json"
                                    class="w-full"
                                    :disabled="!props.jsonSources.length"
                                >
                                    JSON source
                                </ToggleGroupItem>
                            </ToggleGroup>
                        </Field>

                        <template v-if="sourceMode === 'database'">
                            <Field :data-invalid="Boolean(form.errors.entity)">
                                <FieldLabel for="entity">Entity</FieldLabel>
                                <Select
                                    :model-value="form.entity"
                                    @update:model-value="updateEntity"
                                >
                                    <SelectTrigger
                                        id="entity"
                                        :aria-invalid="
                                            Boolean(form.errors.entity)
                                        "
                                    >
                                        <SelectValue
                                            placeholder="Select an entity"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="entity in props.entities"
                                                :key="entity.value"
                                                :value="entity.value"
                                            >
                                                {{ entity.label }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError :errors="[form.errors.entity]" />
                            </Field>

                            <Field
                                :data-invalid="Boolean(form.errors.record_id)"
                            >
                                <FieldLabel for="record">Record</FieldLabel>
                                <Select
                                    :model-value="form.record_id"
                                    @update:model-value="updateRecord"
                                >
                                    <SelectTrigger
                                        id="record"
                                        :aria-invalid="
                                            Boolean(form.errors.record_id)
                                        "
                                    >
                                        <SelectValue
                                            placeholder="Select a record"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="record in selectedEntity?.records ??
                                                []"
                                                :key="record.value"
                                                :value="record.value"
                                            >
                                                {{ record.label }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError :errors="[form.errors.record_id]" />
                            </Field>
                        </template>

                        <template v-else>
                            <Field
                                v-if="props.jsonSources.length"
                                :data-invalid="
                                    Boolean(form.errors.input_source_id)
                                "
                            >
                                <FieldLabel for="json-source"
                                    >JSON source</FieldLabel
                                >
                                <Select
                                    :model-value="
                                        form.input_source_id?.toString() ?? ''
                                    "
                                    @update:model-value="updateInputSource"
                                >
                                    <SelectTrigger
                                        id="json-source"
                                        :aria-invalid="
                                            Boolean(form.errors.input_source_id)
                                        "
                                    >
                                        <SelectValue
                                            placeholder="Select a JSON source"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="source in props.jsonSources"
                                                :key="source.id"
                                                :value="source.id.toString()"
                                            >
                                                {{ source.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError
                                    :errors="[form.errors.input_source_id]"
                                />
                            </Field>

                            <Field
                                v-if="selectedJsonSource"
                                :data-invalid="Boolean(form.errors.record_id)"
                            >
                                <FieldLabel for="json-record"
                                    >JSON record</FieldLabel
                                >
                                <Select
                                    :model-value="form.record_id"
                                    @update:model-value="updateRecord"
                                >
                                    <SelectTrigger
                                        id="json-record"
                                        :aria-invalid="
                                            Boolean(form.errors.record_id)
                                        "
                                    >
                                        <SelectValue
                                            placeholder="Select a JSON record"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem
                                                v-for="record in selectedJsonSource.records"
                                                :key="record.value"
                                                :value="record.value"
                                            >
                                                {{ record.label }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError :errors="[form.errors.record_id]" />
                            </Field>

                            <Alert v-else>
                                <AlertTitle>No JSON sources</AlertTitle>
                                <AlertDescription>
                                    No active JSON sources exist. Ask an
                                    administrator to add one.
                                </AlertDescription>
                            </Alert>
                        </template>
                    </FieldGroup>

                    <Tabs v-model="form.mode" class="flex flex-col gap-4">
                        <TabsList class="w-full">
                            <TabsTrigger value="ai" class="flex-1"
                                >AI</TabsTrigger
                            >
                            <TabsTrigger value="without-ai" class="flex-1">
                                Without AI
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="ai">
                            <FieldGroup>
                                <Field
                                    :data-invalid="Boolean(form.errors.prompt)"
                                >
                                    <FieldLabel for="prompt">Prompt</FieldLabel>
                                    <Textarea
                                        id="prompt"
                                        v-model="form.prompt"
                                        :required="form.mode === 'ai'"
                                        :aria-invalid="
                                            Boolean(form.errors.prompt)
                                        "
                                        rows="7"
                                        placeholder="Describe what you want the AI to do..."
                                    />
                                    <FieldError
                                        :errors="[form.errors.prompt]"
                                    />
                                </Field>
                            </FieldGroup>
                        </TabsContent>

                        <TabsContent value="without-ai">
                            <Alert>
                                <AlertTitle>No AI call</AlertTitle>
                                <AlertDescription>
                                    SQLite needs <code>tags</code> and
                                    <code>rules</code> tables. Use tag column
                                    <code>slug</code>.
                                </AlertDescription>
                            </Alert>
                        </TabsContent>

                        <FieldGroup
                            class="gap-4 rounded-lg border border-dashed p-4"
                        >
                            <Field
                                :data-invalid="
                                    Boolean(
                                        form.errors.knowledge_base_source_id,
                                    )
                                "
                            >
                                <FieldLabel for="knowledge_base_source">
                                    SQLite knowledge base
                                    <span v-if="form.mode === 'without-ai'"
                                        >(required)</span
                                    >
                                    <span v-else>(optional)</span>
                                </FieldLabel>
                                <Select
                                    :model-value="
                                        form.knowledge_base_source_id?.toString() ??
                                        'temporary'
                                    "
                                    @update:model-value="
                                        updateKnowledgeBaseSource
                                    "
                                >
                                    <SelectTrigger
                                        id="knowledge_base_source"
                                        :aria-invalid="
                                            Boolean(
                                                form.errors
                                                    .knowledge_base_source_id,
                                            )
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="temporary">
                                                Upload a temporary database
                                            </SelectItem>
                                            <SelectItem
                                                v-for="source in props.knowledgeBases"
                                                :key="source.id"
                                                :value="source.id.toString()"
                                            >
                                                {{ source.name }}
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <FieldError
                                    :errors="[
                                        form.errors.knowledge_base_source_id,
                                    ]"
                                />
                            </Field>

                            <Field
                                :data-invalid="
                                    Boolean(form.errors.knowledge_base)
                                "
                            >
                                <FieldLabel for="knowledge_base"
                                    >Upload database file</FieldLabel
                                >
                                <Input
                                    id="knowledge_base"
                                    type="file"
                                    accept=".sqlite,.db,application/vnd.sqlite3,application/octet-stream"
                                    :required="
                                        form.mode === 'without-ai' &&
                                        !form.knowledge_base_source_id
                                    "
                                    :disabled="
                                        Boolean(form.knowledge_base_source_id)
                                    "
                                    :aria-invalid="
                                        Boolean(form.errors.knowledge_base)
                                    "
                                    @change="selectKnowledgeBase"
                                />
                                <FieldError
                                    :errors="[form.errors.knowledge_base]"
                                />
                                <FieldDescription>
                                    SQLite is opened read-only. No-AI mode
                                    returns only matching tag rules.
                                </FieldDescription>
                            </Field>

                            <Field
                                v-if="
                                    form.knowledge_base &&
                                    !form.knowledge_base_source_id
                                "
                                :data-invalid="Boolean(form.errors.tag_column)"
                            >
                                <FieldLabel for="tag_column"
                                    >Tag name column</FieldLabel
                                >
                                <Input
                                    id="tag_column"
                                    v-model="form.tag_column"
                                    required
                                    :aria-invalid="
                                        Boolean(form.errors.tag_column)
                                    "
                                    placeholder="For example: slug"
                                />
                                <FieldError
                                    :errors="[form.errors.tag_column]"
                                />
                            </Field>
                        </FieldGroup>
                    </Tabs>

                    <div class="flex items-center justify-end">
                        <Button
                            :disabled="
                                form.processing ||
                                (sourceMode === 'database'
                                    ? !props.entities.length
                                    : !props.jsonSources.length)
                            "
                        >
                            {{
                                form.processing ? 'Sending...' : 'Send request'
                            }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card v-if="props.result">
            <CardHeader>
                <CardTitle>Result</CardTitle>
                <CardDescription>
                    Background AI response for the selected entity.
                </CardDescription>
            </CardHeader>

            <CardContent class="flex flex-col gap-4">
                <div
                    class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm"
                >
                    <div class="flex items-center gap-2">
                        <span class="font-medium">Status:</span>
                        <Badge
                            :variant="
                                props.result.status === 'error'
                                    ? 'destructive'
                                    : 'secondary'
                            "
                        >
                            {{ props.result.status }}
                        </Badge>
                    </div>
                    <p>
                        <span class="font-medium">Entity:</span>
                        {{ props.result.entity_label }}
                    </p>
                    <p>
                        <span class="font-medium">First record:</span>
                        {{ props.result.record_id }}
                    </p>
                </div>

                <Card
                    v-if="props.result.status === 'pending'"
                    class="bg-muted/30"
                >
                    <CardHeader class="p-4">
                        <CardTitle
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span
                                >Waiting for the AI service. This page polls
                                automatically.</span
                            >
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                {{ progressValue }}%
                            </span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4 p-4 pt-0">
                        <Progress :model-value="progressValue" class="h-2.5" />
                        <Alert>
                            <AlertDescription>{{
                                currentPendingMessage
                            }}</AlertDescription>
                        </Alert>
                    </CardContent>
                </Card>

                <Alert v-if="props.result.error" variant="destructive">
                    <AlertTitle>AI service error</AlertTitle>
                    <AlertDescription>{{
                        props.result.error
                    }}</AlertDescription>
                </Alert>

                <Separator />

                <FieldGroup class="gap-4">
                    <Field v-if="props.result.answer">
                        <FieldLabel>Gemma answer</FieldLabel>
                        <pre
                            class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs whitespace-pre-wrap"
                        ><code>{{ props.result.answer }}</code></pre>
                    </Field>

                    <Field>
                        <FieldLabel>AI input payload</FieldLabel>
                        <pre
                            class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs"
                        ><code>{{ JSON.stringify(props.result.input, null, 2) }}</code></pre>
                    </Field>

                    <Field>
                        <FieldLabel>Database row payload</FieldLabel>
                        <pre
                            class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs"
                        ><code>{{ JSON.stringify(props.result.record, null, 2) }}</code></pre>
                    </Field>

                    <Field v-if="props.result.ai_response">
                        <FieldLabel>Raw AI service response</FieldLabel>
                        <pre
                            class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs"
                        ><code>{{ JSON.stringify(props.result.ai_response, null, 2) }}</code></pre>
                    </Field>
                </FieldGroup>
            </CardContent>
        </Card>
    </div>
</template>
