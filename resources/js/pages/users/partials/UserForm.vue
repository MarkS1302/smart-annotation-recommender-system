<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import { isEmpty, map } from 'lodash';
import { useForm } from 'vee-validate';
import * as z from 'zod';
import PasswordInput from '@/components/PasswordInput.vue';
import { Card, CardContent } from '@/components/ui/card';
import GeneralFormField from '@/components/ui/form/GeneralFormField.vue';
import MultiselectInput from '@/components/ui/form/MultiselectInput.vue';
import { Input } from '@/components/ui/input';
import type { Role } from '@/types/models/role';
import type { User } from '@/types/models/user';

type UserFormValues = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    role_ids: [number, ...number[]];
};

type SubmitHelpers = {
    resetForm: () => void;
    setErrors: (errors: Record<string, string>) => void;
};

const { user = null, onSubmit } = defineProps<{
    formId: string;
    roles: Role[];
    user?: User;
    onSubmit: (values: UserFormValues, helpers: SubmitHelpers) => void;
}>();

const formSchema = toTypedSchema(
    z.object({
        name: z.string().min(1),
        email: z.string().min(1).email(),
        password: isEmpty(user) ? z.string().min(5) : z.string().optional(),
        password_confirmation: isEmpty(user)
            ? z.string().min(5)
            : z.string().optional(),
        role_ids: z.array(z.number()).nonempty(),
    }),
);

const initialValues = {
    name: user?.name ?? '',
    email: user?.email ?? '',
    password: '',
    password_confirmation: '',
    role_ids: map(user?.roles, (role: Role) => role.id) ?? [],
} satisfies UserFormValues;

const form = useForm({
    validationSchema: formSchema,
    initialValues,
});

const { handleSubmit, setErrors, resetForm } = form;

const submitForm = handleSubmit((values) => {
    setErrors({});
    onSubmit(values, {
        resetForm,
        setErrors,
    });
});
</script>

<template>
    <form :id="formId" class="space-y-8" @submit.prevent="submitForm">
        <Card>
            <CardContent>
                <div class="flex flex-col space-y-6 py-4">
                    <div class="grid gap-6 md:grid-cols-2">
                        <GeneralFormField name="name" label="Name">
                            <template #default="{ componentField }">
                                <Input
                                    v-bind="componentField"
                                    autocomplete="name"
                                    placeholder="Full name"
                                />
                            </template>
                        </GeneralFormField>

                        <GeneralFormField name="email" label="Email">
                            <template #default="{ componentField }">
                                <Input
                                    v-bind="componentField"
                                    autocomplete="email"
                                    placeholder="name@example.com"
                                    type="email"
                                />
                            </template>
                        </GeneralFormField>

                        <GeneralFormField
                            name="password"
                            :label="
                                isCreate ? 'Password' : 'Password (optional)'
                            "
                            :description="
                                isCreate
                                    ? 'Confirmation required.'
                                    : 'Leave blank to keep the current password.'
                            "
                        >
                            <template #default="{ componentField }">
                                <PasswordInput
                                    v-bind="componentField"
                                    autocomplete="new-password"
                                    placeholder="Password"
                                />
                            </template>
                        </GeneralFormField>

                        <GeneralFormField
                            name="password_confirmation"
                            label="Confirm password"
                        >
                            <template #default="{ componentField }">
                                <PasswordInput
                                    v-bind="componentField"
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                />
                            </template>
                        </GeneralFormField>

                        <GeneralFormField name="role_ids" label="Roles">
                            <template #default="{ componentField }">
                                <MultiselectInput
                                    popover-width="w-auto sm:w-[400px] lg:w-[600px]"
                                    :options="roles"
                                    placeholder="Select"
                                    label-key="name"
                                    value-key="id"
                                    v-bind="componentField"
                                />
                            </template>
                        </GeneralFormField>
                    </div>
                </div>
            </CardContent>
        </Card>
    </form>
</template>
