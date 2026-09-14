<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/roles';

const isOpen = defineModel('isOpen', { default: false });

const form = useForm({
    name: '',
});

watch(isOpen, (value) => {
    if (!value) {
        form.clearErrors();
        form.reset();
    }
});

function closeSheet(): void {
    isOpen.value = false;
}

function submit(): void {
    form.post(store.url(), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeSheet();
            form.reset();
        },
    });
}
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle>Create role</SheetTitle>
                <SheetDescription>Add a new role.</SheetDescription>
            </SheetHeader>

            <form id="create-role-form" class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="role-name">Name</Label>
                    <Input
                        id="role-name"
                        v-model="form.name"
                        autocomplete="off"
                        placeholder="Role name"
                    />
                    <p v-if="form.errors.name" class="text-sm text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>
            </form>

            <div class="flex items-center gap-2 pt-2">
                <SheetClose as-child>
                    <Button type="button" variant="secondary">Cancel</Button>
                </SheetClose>

                <Button
                    form="create-role-form"
                    type="submit"
                    variant="default"
                    :disabled="form.processing"
                >
                    Submit
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
