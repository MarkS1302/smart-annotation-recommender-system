<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { update } from '@/routes/annotation-sources';

type Source = {
    id: number;
    name: string;
    type: 'json' | 'sqlite';
    tag_column: string | null;
    active: boolean;
    json_content?: string | null;
};

const props = defineProps<{
    source: Source | null;
}>();

const isOpen = defineModel<boolean>('isOpen', { default: false });
const fileInputKey = ref(0);

const form = useForm({
    name: '',
    file: null as File | null,
    tag_column: '',
    active: true,
    _method: 'PUT',
});

function selectFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
}

function populateForm(source: Source): void {
    form.name = source.name;
    form.file = null;
    form.tag_column = source.tag_column ?? '';
    form.active = source.active;
    form.clearErrors();
    fileInputKey.value += 1;
}

function resetForm(): void {
    form.reset();
    form.clearErrors();
    fileInputKey.value += 1;
}

function closeSheet(): void {
    isOpen.value = false;
}

function submit(): void {
    if (props.source === null) {
        return;
    }

    form.post(update.url(props.source.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: closeSheet,
    });
}

watch(
    () => props.source,
    (source) => {
        if (source !== null && isOpen.value) {
            populateForm(source);
        }
    },
);

watch(isOpen, (value) => {
    if (value && props.source !== null) {
        populateForm(props.source);
    }

    if (!value) {
        resetForm();
    }
});
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent v-if="props.source" class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle>Edit {{ props.source.name }}</SheetTitle>
                <SheetDescription>
                    Change source settings or replace the stored file.
                </SheetDescription>
            </SheetHeader>

            <form
                id="edit-annotation-source-form"
                class="grid gap-4 overflow-y-auto py-2 pr-2"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label :for="'edit-source-name-' + props.source.id"
                        >Name</Label
                    >
                    <Input
                        :id="'edit-source-name-' + props.source.id"
                        v-model="form.name"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div v-if="props.source.type === 'sqlite'" class="grid gap-2">
                    <Label :for="'edit-tag-column-' + props.source.id"
                        >Tag column</Label
                    >
                    <Input
                        :id="'edit-tag-column-' + props.source.id"
                        v-model="form.tag_column"
                    />
                    <InputError :message="form.errors.tag_column" />
                </div>

                <div v-if="props.source.type === 'json'" class="grid gap-2">
                    <Label :for="'edit-json-content-' + props.source.id"
                        >JSON file contents</Label
                    >
                    <pre
                        :id="'edit-json-content-' + props.source.id"
                        class="max-h-96 overflow-auto rounded-md border bg-muted/30 p-3 text-xs whitespace-pre-wrap"
                        >{{
                            props.source.json_content ??
                            'No JSON file content found.'
                        }}</pre
                    >
                </div>

                <div class="grid gap-2">
                    <Label :for="'edit-source-file-' + props.source.id"
                        >Replace file (optional)</Label
                    >
                    <input
                        :key="fileInputKey"
                        :id="'edit-source-file-' + props.source.id"
                        type="file"
                        :accept="
                            props.source.type === 'sqlite'
                                ? '.sqlite,.db'
                                : '.json,application/json'
                        "
                        class="text-sm"
                        @change="selectFile"
                    />
                    <InputError :message="form.errors.file" />
                </div>

                <Label
                    :for="'edit-source-active-' + props.source.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <Checkbox
                        :id="'edit-source-active-' + props.source.id"
                        :model-value="form.active"
                        @update:model-value="
                            (checked) => (form.active = checked === true)
                        "
                    />
                    <span>Active source</span>
                </Label>
            </form>

            <div class="flex items-center gap-2 pt-2">
                <SheetClose as-child>
                    <Button type="button" variant="secondary">Cancel</Button>
                </SheetClose>

                <Button
                    form="edit-annotation-source-form"
                    type="submit"
                    variant="default"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : 'Save changes' }}
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
