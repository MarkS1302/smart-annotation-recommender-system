<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Users/UserController';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import UserForm from '@/pages/users/partials/UserForm.vue';
import type { User } from '@/types/models/user';
import { Role } from '@/types/models/role';

const { user } = defineProps<{
    roles: Role[];
    user: User;
}>();

const isOpen = defineModel('isOpen', { default: false });

function closeSheet(): void {
    isOpen.value = false;
}

function updateUser(
    values: Record<string, unknown>,
    {
        resetForm,
        setErrors,
    }: {
        resetForm: () => void;
        setErrors: (errors: Record<string, string>) => void;
    },
): void {
    const { method, url } = UserController.update(user.id);

    router.visit(url, {
        method,
        data: {
            ...values,
        },
        only: ['errors', 'usersResourceCollection'],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            resetForm();
            closeSheet();
        },
        onError: (errors) => setErrors(errors),
    });
}
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle>Edit User Form</SheetTitle>
                <SheetDescription> Description </SheetDescription>
            </SheetHeader>

            <UserForm
                :user="user"
                form-id="edit-user-form"
                :roles="roles"
                :on-submit="updateUser"
            />

            <div class="flex items-center gap-2 pt-2">
                <SheetClose as-child>
                    <Button type="button" variant="secondary"> Cancel </Button>
                </SheetClose>

                <Button form="edit-user-form" type="submit" variant="default">
                    Submit
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
