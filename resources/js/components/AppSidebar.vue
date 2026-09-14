<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Activity, Bot, ClipboardList, LayoutGrid, Shield, Users } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as aiRequestsIndex } from '@/routes/ai-requests';
import { index as aiResponsesIndex } from '@/routes/ai-responses';
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as auditsIndex } from '@/routes/audits';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'AI requests',
        href: aiRequestsIndex(),
        icon: Bot,
    },
    {
        title: 'AI responses',
        href: aiResponsesIndex(),
        icon: Bot,
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: Users,
    },
    {
        title: 'Roles',
        href: rolesIndex(),
        icon: Shield,
    },
    {
        title: 'Audits',
        href: auditsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Activity logs',
        href: activityLogsIndex(),
        icon: Activity,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
