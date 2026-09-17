<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { store } from '@/routes/annotation-sources';

type SourceType = 'json' | 'sqlite';

const isOpen = defineModel<boolean>('isOpen', { default: false });
const fileInputKey = ref(0);

const form = useForm({
    name: '',
    type: 'sqlite' as SourceType,
    file: null as File | null,
    tag_column: 'slug',
    active: true,
});

function selectFile(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
}

function selectType(value: unknown): void {
    if (value === 'json' || value === 'sqlite') {
        form.type = value;
    }
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
    form.post(store.url(), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: closeSheet,
    });
}

watch(isOpen, (value) => {
    if (!value) {
        resetForm();
    }
});
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle>Add annotation source</SheetTitle>
                <SheetDescription>
                    Add a SQLite knowledge base or JSON records file for
                    annotation requests.
                </SheetDescription>
            </SheetHeader>

            <form
                id="create-annotation-source-form"
                class="grid gap-4 overflow-y-auto py-2 pr-2"
                @submit.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="source-name">Name</Label>
                    <Input
                        id="source-name"
                        v-model="form.name"
                        placeholder="Customer taxonomy"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="source-type">Type</Label>
                    <Select
                        :model-value="form.type"
                        @update:model-value="selectType"
                    >
                        <SelectTrigger id="source-type" class="w-full">
                            <SelectValue placeholder="Choose source type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="sqlite"
                                    >SQLite knowledge base</SelectItem
                                >
                                <SelectItem value="json"
                                    >JSON records</SelectItem
                                >
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="grid gap-2">
                    <Label for="source-file">File</Label>
                    <input
                        :key="fileInputKey"
                        id="source-file"
                        type="file"
                        :accept="
                            form.type === 'sqlite'
                                ? '.sqlite,.db'
                                : '.json,application/json'
                        "
                        required
                        class="text-sm"
                        @change="selectFile"
                    />
                    <InputError :message="form.errors.file" />
                </div>

                <div v-if="form.type === 'sqlite'" class="grid gap-2">
                    <Label for="source-tag-column">Tag column</Label>
                    <Input
                        id="source-tag-column"
                        v-model="form.tag_column"
                        placeholder="slug"
                    />
                    <InputError :message="form.errors.tag_column" />
                </div>

                <Label
                    for="source-active"
                    class="flex items-center gap-2 text-sm"
                >
                    <Checkbox
                        id="source-active"
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
                    form="create-annotation-source-form"
                    type="submit"
                    variant="default"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : 'Save source' }}
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
