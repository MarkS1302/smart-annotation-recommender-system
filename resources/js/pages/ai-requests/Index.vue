<script setup lang="ts">
import { Head, useForm, usePoll } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index, store } from '@/routes/ai-requests';

type EntityOption = {
    value: string;
    label: string;
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

const props = defineProps<{
    defaultPrompt: string;
    entities: EntityOption[];
    requestId?: string | null;
    result?: RequestResult | null;
}>();

const firstEntity = props.entities[0] ?? null;

const form = useForm({
    entity: firstEntity?.value ?? '',
    mode: 'ai',
    prompt: props.defaultPrompt,
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

let progressInterval: ReturnType<typeof setInterval> | null = null;
let messageInterval: ReturnType<typeof setInterval> | null = null;

const currentPendingMessage = computed(
    () => pendingMessages[messageIndex.value],
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
                    <div class="grid gap-2">
                        <Label for="entity">Entity</Label>
                        <Select
                            :model-value="form.entity"
                            @update:model-value="
                                (value) => {
                                    form.entity = value?.toString() ?? '';
                                }
                            "
                        >
                            <SelectTrigger
                                id="entity"
                                :aria-invalid="Boolean(form.errors.entity)"
                            >
                                <SelectValue placeholder="Select an entity" />
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
                        <InputError :message="form.errors.entity" />
                        <p class="text-sm text-muted-foreground">
                            Backend sends the first available row from this
                            entity.
                        </p>
                    </div>

                    <Tabs v-model="form.mode" class="grid gap-4">
                        <TabsList class="w-full">
                            <TabsTrigger value="ai" class="flex-1"
                                >AI</TabsTrigger
                            >
                            <TabsTrigger value="without-ai" class="flex-1">
                                Without AI
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="ai" class="grid gap-6">
                            <div class="grid gap-2">
                                <Label for="prompt">Prompt</Label>
                                <textarea
                                    id="prompt"
                                    v-model="form.prompt"
                                    :required="form.mode === 'ai'"
                                    rows="7"
                                    placeholder="Describe what you want the AI to do..."
                                    class="min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                                />
                                <InputError :message="form.errors.prompt" />
                            </div>
                        </TabsContent>

                        <TabsContent
                            value="without-ai"
                            class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                        >
                            No AI call. SQLite needs <code>tags</code> and
                            <code>rules</code> tables. Use tag column
                            <code>slug</code>.
                        </TabsContent>

                        <div
                            class="grid gap-2 rounded-lg border border-dashed p-4"
                        >
                            <Label for="knowledge_base">
                                SQLite knowledge DB
                                <span v-if="form.mode === 'without-ai'"
                                    >(required)</span
                                >
                                <span v-else>(optional)</span>
                            </Label>
                            <input
                                id="knowledge_base"
                                type="file"
                                accept=".sqlite,.db,application/vnd.sqlite3,application/octet-stream"
                                :required="form.mode === 'without-ai'"
                                class="text-sm"
                                @change="selectKnowledgeBase"
                            />
                            <InputError :message="form.errors.knowledge_base" />
                            <p class="text-sm text-muted-foreground">
                                SQLite is opened read-only. No-AI mode returns
                                only matching tag rules.
                            </p>

                            <div v-if="form.knowledge_base" class="grid gap-2">
                                <Label for="tag_column">Tag name column</Label>
                                <input
                                    id="tag_column"
                                    v-model="form.tag_column"
                                    required
                                    placeholder="For example: slug"
                                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                                />
                                <InputError :message="form.errors.tag_column" />
                            </div>
                        </div>
                    </Tabs>

                    <div class="flex items-center justify-end">
                        <Button
                            :disabled="
                                form.processing || !props.entities.length
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
                <div class="grid gap-1 text-sm">
                    <p>
                        <span class="font-medium">Status:</span>
                        {{ props.result.status }}
                    </p>
                    <p>
                        <span class="font-medium">Entity:</span>
                        {{ props.result.entity_label }}
                    </p>
                    <p>
                        <span class="font-medium">First record:</span>
                        {{ props.result.record_id }}
                    </p>
                </div>

                <div
                    v-if="props.result.status === 'pending'"
                    class="flex flex-col gap-4 rounded-xl border bg-muted/30 p-4"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-medium">
                            Waiting for the AI service. This page polls
                            automatically.
                        </p>
                        <span class="text-xs text-muted-foreground">
                            {{ progressValue }}%
                        </span>
                    </div>

                    <Progress :model-value="progressValue" class="h-2.5" />

                    <div
                        class="rounded-lg border border-dashed bg-background/70 px-3 py-2 text-sm text-muted-foreground"
                    >
                        {{ currentPendingMessage }}
                    </div>
                </div>

                <div v-if="props.result.error" class="grid gap-2">
                    <Label>AI service error</Label>
                    <div
                        class="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
                    >
                        {{ props.result.error }}
                    </div>
                </div>

                <div v-if="props.result.answer" class="grid gap-2">
                    <Label>Gemma answer</Label>
                    <pre
                        class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs whitespace-pre-wrap"
                    ><code>{{ props.result.answer }}</code></pre>
                </div>

                <div class="grid gap-2">
                    <Label>AI input payload</Label>
                    <pre
                        class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs"
                    ><code>{{ JSON.stringify(props.result.input, null, 2) }}</code></pre>
                </div>

                <div class="grid gap-2">
                    <Label>Database row payload</Label>
                    <pre
                        class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs"
                    ><code>{{ JSON.stringify(props.result.record, null, 2) }}</code></pre>
                </div>

                <div v-if="props.result.ai_response" class="grid gap-2">
                    <Label>Raw AI service response</Label>
                    <pre
                        class="overflow-x-auto rounded-md border bg-muted/30 p-3 text-xs"
                    ><code>{{ JSON.stringify(props.result.ai_response, null, 2) }}</code></pre>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
