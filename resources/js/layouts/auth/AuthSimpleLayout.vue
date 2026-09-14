<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="dashboard()"
                        class="flex items-center gap-3 font-medium"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                        >
                            <AppLogoIcon
                                class="size-6 fill-current text-white dark:text-black"
                            />
                        </div>
                        <div class="space-y-1 text-left">
                            <h1 class="text-xl font-medium">{{ title }}</h1>
                            <p class="text-sm text-muted-foreground">
                                {{ description }}
                            </p>
                        </div>
                    </Link>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
