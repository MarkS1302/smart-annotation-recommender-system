<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Moon, Settings, Sun } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator
} from '@/components/ui/dropdown-menu';
import { Switch } from '@/components/ui/switch';
import UserInfo from '@/components/UserInfo.vue';
import { useAppearance } from '@/composables/useAppearance';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDarkMode = computed(() => resolvedAppearance.value === 'dark');

function toggleDarkMode(checked: boolean): void {
    updateAppearance(checked ? 'dark' : 'light');
}

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <div class="flex items-center justify-between gap-4 px-2 py-2.5">
        <span class="text-sm">Dark Mode</span>

        <Switch :model-value="isDarkMode" @update:model-value="toggleDarkMode">
            <template #default="{ checked }">
                <Moon v-if="checked" class="size-3.5" />
                <Sun v-else class="size-3.5" />
            </template>
        </Switch>
    </div>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
