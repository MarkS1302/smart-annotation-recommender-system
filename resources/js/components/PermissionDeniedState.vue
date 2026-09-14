<script setup lang="ts">
defineProps<{
    resource: string;
    action: string;
    message?: string;
}>();
</script>

<template>
    <div class="flex min-h-[60vh] items-center justify-center">
        <div class="max-w-xl rounded-2xl border bg-card p-8 text-center shadow-sm">
            <p class="text-lg font-semibold">You dont have permission</p>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ message ?? `You dont have permission to ${action} ${resource}. Speak with an admin.` }}
            </p>
        </div>
    </div>
</template>
