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
import { update } from '@/routes/roles';

type Role = {
    id: number;
    name: string;
};

const props = defineProps<{
    role: Role | null;
}>();

const isOpen = defineModel('isOpen', { default: false });

const form = useForm({
    name: props.role?.name ?? '',
});

watch(
    () => props.role,
    (role) => {
        form.name = role?.name ?? '';
        form.clearErrors();
    },
);

watch(isOpen, (value) => {
    if (!value) {
        form.clearErrors();
        form.name = props.role?.name ?? '';
    }
});

function closeSheet(): void {
    isOpen.value = false;
}

function submit(): void {
    if (!props.role) {
        return;
    }

    form.put(update(props.role).url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            closeSheet();
        },
    });
}
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle>Edit role</SheetTitle>
                <SheetDescription>Update the role name.</SheetDescription>
            </SheetHeader>

            <form id="edit-role-form" class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="edit-role-name">Name</Label>
                    <Input
                        id="edit-role-name"
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
                    form="edit-role-form"
                    type="submit"
                    variant="default"
                    :disabled="form.processing || !role"
                >
                    Submit
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
