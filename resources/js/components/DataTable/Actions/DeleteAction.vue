<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Loader2, Trash2 } from '@lucide/vue';
import { isFunction } from 'lodash';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

const props = defineProps<{
    actionRoute: string;
    title?: string;
    message?: string;
    propsToReload?: string[];
    onClickHandler?: () => void;
    onSuccess?: () => void;
}>();

const isLoading = ref(false);
const isOpen = ref(false);

function handleDelete(): void {
    if (isFunction(props.onClickHandler)) {
        props.onClickHandler();
        return;
    }

    isLoading.value = true;

    router.delete(props.actionRoute, {
        only: props.propsToReload ?? [],
        preserveState: true,
        onSuccess: () => {
            isOpen.value = false;
            props.onSuccess?.();
        },
        onFinish: () => {
            isLoading.value = false;
            toast.success('Deleted');
        },
        onError: () => {
            toast.error('Delete failed');
        },
    });
}
</script>

<template>
    <AlertDialog v-model:open="isOpen">
        <AlertDialogTrigger as-child>
            <Button size="sm" variant="ghost">
                <Trash2 class="h-4 w-4" />
            </Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title ?? 'Delete item?' }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ message ?? 'This action cannot be undone.' }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction :disabled="isLoading" @click="handleDelete">
                    <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                    Delete
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
