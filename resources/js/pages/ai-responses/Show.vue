<script setup lang="ts">
import { Head, Link, usePoll } from '@inertiajs/vue3';
import { AlertCircle, ChevronDown } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import PermissionDeniedState from '@/components/PermissionDeniedState.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
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
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { index } from '@/routes/ai-responses';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { Resource } from '@/types/response/resource';

type AiResponse = {
    id: number;
    request_id: string;
    entity: string;
    record_id: string;
    status: 'pending' | 'success' | 'error';
    answer: string | null;
    ai_response: Record<string, unknown> | null;
    error: string | null;
    created_at: string | null;
    updated_at: string | null;
};

type Annotation = {
    tag?: string;
    label?: string;
    category?: string;
    confidence?: number | string | null;
    reason?: string;
    source_fields?: string[] | string | null;
    existing_tag?: boolean | null;
    [key: string]: unknown;
};

type IgnoredField = {
    field: string;
    reason?: string;
};

type Answer = {
    model?: string;
    entity_type?: string;
    summary?: string;
    annotations?: Annotation[];
    uncertain_annotations?: Annotation[];
    ignored_fields?: Array<string | IgnoredField>;
    [key: string]: unknown;
};

const { aiResponse } = defineProps<{
    aiResponse: Resource<AiResponse>;
}>();
const { canView } = usePermissions();

const pendingMessages = [
    'Reading the request and checking the selected record.',
    'Looking for useful patterns in the record fields.',
    'Comparing possible tags with the annotation taxonomy.',
    'Checking confidence and preparing the final answer.',
];

usePoll(5000, {
    only: ['aiResponse'],
});

const pendingMessageIndex = ref(0);
const pendingProgress = ref(12);
let pendingAnimationInterval: ReturnType<typeof setInterval> | null = null;

const currentPendingMessage = computed(
    () => pendingMessages[pendingMessageIndex.value],
);

watch(
    () => aiResponse.data.status,
    (status) => {
        if (status === 'pending') {
            beginPendingAnimation();

            return;
        }

        stopPendingAnimation();

        if (status === 'success') {
            pendingProgress.value = 100;
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    stopPendingAnimation();
});

function beginPendingAnimation(): void {
    if (pendingAnimationInterval !== null) {
        return;
    }

    pendingAnimationInterval = setInterval(() => {
        pendingMessageIndex.value =
            (pendingMessageIndex.value + 1) % pendingMessages.length;
        pendingProgress.value = Math.min(
            92,
            pendingProgress.value + Math.floor(Math.random() * 6) + 2,
        );
    }, 2400);
}

function stopPendingAnimation(): void {
    if (pendingAnimationInterval === null) {
        return;
    }

    clearInterval(pendingAnimationInterval);
    pendingAnimationInterval = null;
}

function prettyJson(value: Record<string, unknown> | null): string {
    return JSON.stringify(value ?? {}, null, 2);
}

function humanizeKey(key: string): string {
    return key
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function isSimpleValue(value: unknown): boolean {
    return (
        value === null || ['string', 'number', 'boolean'].includes(typeof value)
    );
}

function simpleValue(value: unknown): string {
    if (value === null) {
        return 'Not provided';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    return String(value);
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function parseAnswer(answer: string | null): Answer | null {
    if (!answer) {
        return null;
    }

    const normalizedAnswer = answer
        .trim()
        .replace(/^```(?:json)?\s*/i, '')
        .replace(/\s*```$/i, '');

    try {
        const parsedAnswer: unknown = JSON.parse(normalizedAnswer);

        return isRecord(parsedAnswer) ? (parsedAnswer as Answer) : null;
    } catch {
        return null;
    }
}

function annotationList(value: unknown): Annotation[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.filter(isRecord) as Annotation[];
}

function stringList(value: unknown): string[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.filter((item): item is string => typeof item === 'string');
}

function ignoredFieldList(value: unknown): IgnoredField[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.flatMap((item): IgnoredField[] => {
        if (typeof item === 'string') {
            const field = item.trim();

            return field ? [{ field }] : [];
        }

        if (!isRecord(item) || typeof item.field !== 'string') {
            return [];
        }

        const field = item.field.trim();

        if (!field) {
            return [];
        }

        return [
            {
                field,
                reason:
                    typeof item.reason === 'string'
                        ? item.reason
                        : undefined,
            },
        ];
    });
}

function textValue(value: unknown, fallback = 'Not provided'): string {
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
}

function sourceFields(annotation: Annotation): string[] {
    if (typeof annotation.source_fields === 'string') {
        return [annotation.source_fields];
    }

    return stringList(annotation.source_fields);
}

function confidencePercent(value: Annotation['confidence']): number | null {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const numericValue = typeof value === 'number' ? value : Number(value);

    if (!Number.isFinite(numericValue)) {
        return null;
    }

    const percentage = numericValue <= 1 ? numericValue * 100 : numericValue;

    return Math.min(100, Math.max(0, Math.round(percentage)));
}

function confidenceLabel(value: Annotation['confidence']): string {
    const percentage = confidencePercent(value);

    return percentage === null ? 'Not scored' : `${percentage}% confidence`;
}

function valueJson(value: unknown): string {
    if (typeof value === 'string') {
        return value;
    }

    return JSON.stringify(value, null, 2) ?? 'Not provided';
}

const parsedAnswer = computed(() => parseAnswer(aiResponse.data.answer));
const rawResponseEntries = computed(() =>
    Object.entries(aiResponse.data.ai_response ?? {}),
);
const annotations = computed(() =>
    annotationList(parsedAnswer.value?.annotations),
);
const uncertainAnnotations = computed(() =>
    annotationList(parsedAnswer.value?.uncertain_annotations),
);
const ignoredFields = computed(() =>
    ignoredFieldList(parsedAnswer.value?.ignored_fields),
);
const formattedAnswer = computed(() => {
    if (parsedAnswer.value) {
        return JSON.stringify(parsedAnswer.value, null, 2);
    }

    return aiResponse.data.answer ?? '';
});

const answerExtraEntries = computed(() => {
    const knownKeys = new Set([
        'model',
        'entity_type',
        'summary',
        'annotations',
        'uncertain_annotations',
        'ignored_fields',
    ]);

    return Object.entries(parsedAnswer.value ?? {}).filter(
        ([key]) => !knownKeys.has(key),
    );
});

function annotationExtraEntries(
    annotation: Annotation,
): Array<[string, unknown]> {
    const knownKeys = new Set([
        'tag',
        'label',
        'category',
        'confidence',
        'reason',
        'source_fields',
        'existing_tag',
    ]);

    return Object.entries(annotation).filter(([key]) => !knownKeys.has(key));
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'AI response',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <div class="space-y-6">
        <Head title="AI response" />

        <PermissionDeniedState
            v-if="!canView('ai-responses')"
            resource="AI responses"
            action="view"
        />

        <div v-else class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="AI response"
                description="Stored result from the annotation request."
            />

            <div class="flex items-center gap-2">
                <Button variant="secondary" as-child>
                    <Link :href="index()">All responses</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Status</p>
                <Badge
                    class="mt-2"
                    :variant="
                        aiResponse.data.status === 'error'
                            ? 'destructive'
                            : aiResponse.data.status === 'pending'
                              ? 'secondary'
                              : 'default'
                    "
                >
                    {{ aiResponse.data.status }}
                </Badge>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Entity</p>
                <p class="mt-1 font-medium capitalize">
                    {{ aiResponse.data.entity }}
                </p>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Record</p>
                <p class="mt-1 font-medium">#{{ aiResponse.data.record_id }}</p>
            </div>

            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Created</p>
                <p class="mt-1 text-sm font-medium">
                    {{ aiResponse.data.created_at }}
                </p>
            </div>
        </div>

        <Card
            v-if="aiResponse.data.status === 'pending'"
            class="overflow-hidden"
            aria-live="polite"
        >
            <CardHeader>
                <div class="flex items-center gap-3">
                    <div
                        class="relative flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                    >
                        <span
                            class="absolute size-8 animate-ping rounded-full bg-primary/20 motion-reduce:animate-none"
                        />
                        <Spinner class="relative size-5" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <CardTitle>AI is thinking...</CardTitle>
                        <CardDescription>
                            {{ currentPendingMessage }}
                        </CardDescription>
                    </div>
                </div>
            </CardHeader>

            <CardContent class="grid gap-2">
                <div class="grid gap-2">
                    <div
                        class="flex items-center justify-between gap-3 text-sm"
                    >
                        <span class="font-medium">Thinking activity</span>
                        <span class="text-muted-foreground"
                            >{{ pendingProgress }}%</span
                        >
                    </div>
                    <Progress
                        :model-value="pendingProgress"
                        aria-label="Estimated AI thinking progress"
                    />
                    <p class="text-xs text-muted-foreground">
                        Estimate only. The page checks for the finished answer
                        every 5 seconds.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Alert v-if="aiResponse.data.error" variant="destructive">
            <AlertCircle class="size-4" />
            <AlertDescription>{{ aiResponse.data.error }}</AlertDescription>
        </Alert>

        <Card v-if="aiResponse.data.answer">
            <CardHeader>
                <div
                    class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"
                >
                    <div class="flex flex-col gap-1">
                        <p class="text-sm text-muted-foreground">
                            Structured answer
                        </p>
                        <CardTitle class="text-lg">
                            {{
                                textValue(
                                    parsedAnswer?.summary,
                                    'Annotation recommendations',
                                )
                            }}
                        </CardTitle>
                        <CardDescription
                            v-if="
                                parsedAnswer?.model || parsedAnswer?.entity_type
                            "
                        >
                            {{
                                textValue(
                                    parsedAnswer?.model,
                                    textValue(parsedAnswer?.entity_type),
                                )
                            }}
                        </CardDescription>
                    </div>

                    <div v-if="parsedAnswer" class="flex flex-wrap gap-2">
                        <Badge variant="outline">
                            {{ annotations.length }}
                            {{
                                annotations.length === 1
                                    ? 'annotation'
                                    : 'annotations'
                            }}
                        </Badge>
                        <Badge
                            v-if="uncertainAnnotations.length"
                            variant="secondary"
                        >
                            {{ uncertainAnnotations.length }} uncertain
                        </Badge>
                    </div>
                </div>
            </CardHeader>

            <CardContent v-if="parsedAnswer" class="grid gap-6">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg bg-muted/40 p-4">
                        <p class="text-sm text-muted-foreground">Recommended</p>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ annotations.length }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-muted/40 p-4">
                        <p class="text-sm text-muted-foreground">
                            Ignored fields
                        </p>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ ignoredFields.length }}
                        </p>
                    </div>
                </div>

                <div v-if="annotations.length" class="grid gap-3">
                    <div>
                        <h3 class="font-medium">Recommended annotations</h3>
                        <p class="text-sm text-muted-foreground">
                            Expand an annotation to see why it was suggested and
                            which fields support it.
                        </p>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        <Collapsible
                            v-for="(annotation, annotationIndex) in annotations"
                            :key="`${annotation.tag ?? 'annotation'}-${annotationIndex}`"
                            :default-open="true"
                            class="rounded-lg border bg-background"
                        >
                            <CollapsibleTrigger as-child>
                                <button
                                    type="button"
                                    class="group flex w-full items-start justify-between gap-4 p-4 text-left"
                                >
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span class="font-medium">
                                                {{
                                                    textValue(
                                                        annotation.label,
                                                        textValue(
                                                            annotation.tag,
                                                            'Untitled annotation',
                                                        ),
                                                    )
                                                }}
                                            </span>
                                            <Badge
                                                v-if="annotation.category"
                                                variant="outline"
                                            >
                                                {{ annotation.category }}
                                            </Badge>
                                        </div>
                                        <p
                                            v-if="annotation.tag"
                                            class="truncate font-mono text-xs text-muted-foreground"
                                        >
                                            {{ annotation.tag }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-2"
                                    >
                                        <Badge>{{
                                            confidenceLabel(
                                                annotation.confidence,
                                            )
                                        }}</Badge>
                                        <ChevronDown
                                            class="size-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                                        />
                                    </div>
                                </button>
                            </CollapsibleTrigger>

                            <CollapsibleContent>
                                <div class="grid gap-4 border-t p-4 text-sm">
                                    <div
                                        v-if="
                                            confidencePercent(
                                                annotation.confidence,
                                            ) !== null
                                        "
                                        class="grid gap-2"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3 text-muted-foreground"
                                        >
                                            <span>Confidence</span>
                                            <span
                                                class="font-medium text-foreground"
                                                >{{
                                                    confidenceLabel(
                                                        annotation.confidence,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            class="h-2 overflow-hidden rounded-full bg-muted"
                                        >
                                            <div
                                                class="h-full rounded-full bg-primary transition-[width]"
                                                :style="{
                                                    width: `${confidencePercent(annotation.confidence)}%`,
                                                }"
                                            />
                                        </div>
                                    </div>

                                    <dl class="grid gap-3 sm:grid-cols-2">
                                        <div
                                            v-if="annotation.tag"
                                            class="grid gap-1"
                                        >
                                            <dt
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                Tag
                                            </dt>
                                            <dd class="font-mono text-xs">
                                                {{ annotation.tag }}
                                            </dd>
                                        </div>
                                        <div
                                            v-if="
                                                annotation.existing_tag !==
                                                undefined
                                            "
                                            class="grid gap-1"
                                        >
                                            <dt
                                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                            >
                                                Existing tag
                                            </dt>
                                            <dd>
                                                {{
                                                    annotation.existing_tag
                                                        ? 'Yes'
                                                        : 'No'
                                                }}
                                            </dd>
                                        </div>
                                    </dl>

                                    <div
                                        v-if="annotation.reason"
                                        class="grid gap-1"
                                    >
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Why this was suggested
                                        </p>
                                        <p>{{ annotation.reason }}</p>
                                    </div>

                                    <div
                                        v-if="sourceFields(annotation).length"
                                        class="grid gap-2"
                                    >
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Source fields
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <Badge
                                                v-for="field in sourceFields(
                                                    annotation,
                                                )"
                                                :key="field"
                                                variant="secondary"
                                            >
                                                {{ field }}
                                            </Badge>
                                        </div>
                                    </div>

                                    <Collapsible
                                        v-if="
                                            annotationExtraEntries(annotation)
                                                .length
                                        "
                                        class="rounded-md border border-dashed p-3"
                                    >
                                        <CollapsibleTrigger as-child>
                                            <button
                                                type="button"
                                                class="group flex w-full items-center justify-between text-left font-medium"
                                            >
                                                More details
                                                <ChevronDown
                                                    class="size-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                                                />
                                            </button>
                                        </CollapsibleTrigger>
                                        <CollapsibleContent>
                                            <dl class="mt-3 grid gap-3">
                                                <div
                                                    v-for="[
                                                        key,
                                                        value,
                                                    ] in annotationExtraEntries(
                                                        annotation,
                                                    )"
                                                    :key="key"
                                                    class="grid gap-1"
                                                >
                                                    <dt
                                                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                                    >
                                                        {{
                                                            key.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )
                                                        }}
                                                    </dt>
                                                    <dd
                                                        class="text-xs break-words whitespace-pre-wrap"
                                                    >
                                                        {{ valueJson(value) }}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>

                <div v-if="uncertainAnnotations.length" class="grid gap-3">
                    <div>
                        <h3 class="font-medium">Needs review</h3>
                        <p class="text-sm text-muted-foreground">
                            These suggestions have lower certainty and should be
                            checked before applying.
                        </p>
                    </div>

                    <div class="grid gap-3 lg:grid-cols-2">
                        <Collapsible
                            v-for="(
                                annotation, annotationIndex
                            ) in uncertainAnnotations"
                            :key="`uncertain-${annotation.tag ?? 'annotation'}-${annotationIndex}`"
                            class="rounded-lg border border-amber-500/30 bg-amber-500/5"
                        >
                            <CollapsibleTrigger as-child>
                                <button
                                    type="button"
                                    class="group flex w-full items-start justify-between gap-4 p-4 text-left"
                                >
                                    <div class="flex min-w-0 flex-col gap-1">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span class="font-medium">
                                                {{
                                                    textValue(
                                                        annotation.label,
                                                        textValue(
                                                            annotation.tag,
                                                            'Untitled annotation',
                                                        ),
                                                    )
                                                }}
                                            </span>
                                            <Badge
                                                v-if="annotation.category"
                                                variant="outline"
                                            >
                                                {{ annotation.category }}
                                            </Badge>
                                        </div>
                                        <p
                                            v-if="annotation.tag"
                                            class="truncate font-mono text-xs text-muted-foreground"
                                        >
                                            {{ annotation.tag }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-2"
                                    >
                                        <Badge variant="secondary">{{
                                            confidenceLabel(
                                                annotation.confidence,
                                            )
                                        }}</Badge>
                                        <ChevronDown
                                            class="size-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                                        />
                                    </div>
                                </button>
                            </CollapsibleTrigger>

                            <CollapsibleContent>
                                <div
                                    class="grid gap-4 border-t border-amber-500/20 p-4 text-sm"
                                >
                                    <div
                                        v-if="annotation.reason"
                                        class="grid gap-1"
                                    >
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Why this needs review
                                        </p>
                                        <p>{{ annotation.reason }}</p>
                                    </div>
                                    <div
                                        v-if="sourceFields(annotation).length"
                                        class="grid gap-2"
                                    >
                                        <p
                                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                        >
                                            Source fields
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <Badge
                                                v-for="field in sourceFields(
                                                    annotation,
                                                )"
                                                :key="field"
                                                variant="secondary"
                                            >
                                                {{ field }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>

                <div
                    v-if="ignoredFields.length"
                    class="grid gap-2 rounded-lg border border-dashed p-4"
                >
                    <h3 class="font-medium">Ignored fields</h3>
                    <p class="text-sm text-muted-foreground">
                        The answer skipped these fields when making
                        recommendations.
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div
                            v-for="(
                                ignoredField, ignoredFieldIndex
                            ) in ignoredFields"
                            :key="`${ignoredField.field}-${ignoredFieldIndex}`"
                            class="grid gap-1 rounded-md bg-muted/40 p-3"
                        >
                            <Badge variant="secondary" class="w-fit">
                                {{ ignoredField.field }}
                            </Badge>
                            <p
                                v-if="ignoredField.reason"
                                class="text-sm text-muted-foreground"
                            >
                                {{ ignoredField.reason }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="
                        !annotations.length &&
                        !uncertainAnnotations.length &&
                        !ignoredFields.length
                    "
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    No annotation details were returned. Open the raw JSON below
                    to inspect the complete answer.
                </div>

                <div v-if="answerExtraEntries.length" class="grid gap-2">
                    <h3 class="font-medium">Additional answer details</h3>
                    <dl class="grid gap-3 rounded-lg border p-4 sm:grid-cols-2">
                        <div
                            v-for="[key, value] in answerExtraEntries"
                            :key="key"
                            class="grid gap-1"
                        >
                            <dt
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                {{ key.replaceAll('_', ' ') }}
                            </dt>
                            <dd class="text-sm break-words whitespace-pre-wrap">
                                {{ valueJson(value) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <Collapsible class="rounded-lg border border-dashed p-4">
                    <CollapsibleTrigger as-child>
                        <button
                            type="button"
                            class="group flex w-full items-center justify-between text-left font-medium"
                        >
                            View raw answer JSON
                            <ChevronDown
                                class="size-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                            />
                        </button>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <pre
                            class="mt-3 max-h-96 overflow-auto rounded-lg bg-muted p-4 text-xs whitespace-pre-wrap"
                        ><code>{{ formattedAnswer }}</code></pre>
                    </CollapsibleContent>
                </Collapsible>
            </CardContent>

            <CardContent
                v-else
                class="grid gap-4 p-4 text-sm text-muted-foreground"
            >
                This answer is not valid JSON, so it is shown as plain text
                below.
                <pre
                    class="mt-3 overflow-x-auto rounded-lg bg-muted p-4 text-xs whitespace-pre-wrap"
                ><code>{{ formattedAnswer }}</code></pre>
            </CardContent>
        </Card>

        <Card v-if="aiResponse.data.ai_response">
            <CardHeader>
                <CardTitle>AI service response</CardTitle>
                <CardDescription>
                    Readable view of the response returned by the AI service.
                </CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4">
                <div class="grid gap-3 sm:grid-cols-2">
                    <template
                        v-for="[key, value] in rawResponseEntries"
                        :key="key"
                    >
                        <div
                            v-if="isSimpleValue(value)"
                            class="grid gap-1 rounded-lg border bg-muted/20 p-4"
                        >
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                {{ humanizeKey(key) }}
                            </p>
                            <p class="text-sm font-medium break-words">
                                {{ simpleValue(value) }}
                            </p>
                        </div>

                        <Collapsible
                            v-else
                            class="rounded-lg border bg-muted/20 p-4"
                        >
                            <CollapsibleTrigger as-child>
                                <button
                                    type="button"
                                    class="group flex w-full items-center justify-between gap-3 text-left font-medium"
                                >
                                    {{ humanizeKey(key) }}
                                    <ChevronDown
                                        class="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                                    />
                                </button>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <pre
                                    class="mt-3 max-h-80 overflow-auto rounded-lg bg-muted p-4 text-xs whitespace-pre-wrap"
                                ><code>{{ valueJson(value) }}</code></pre>
                            </CollapsibleContent>
                        </Collapsible>
                    </template>
                </div>

                <Collapsible class="rounded-lg border border-dashed p-4">
                    <CollapsibleTrigger as-child>
                        <button
                            type="button"
                            class="group flex w-full items-center justify-between text-left font-medium"
                        >
                            View complete response JSON
                            <ChevronDown
                                class="size-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                            />
                        </button>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <pre
                            class="mt-3 max-h-96 overflow-auto rounded-lg bg-muted p-4 text-xs whitespace-pre-wrap"
                        ><code>{{ prettyJson(aiResponse.data.ai_response) }}</code></pre>
                    </CollapsibleContent>
                </Collapsible>
            </CardContent>
        </Card>
        </div>
    </div>
</template>
