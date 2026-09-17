<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Activity, Bot, ClipboardList, Database, LayoutGrid, Shield, Users } from '@lucide/vue';
import { computed } from 'vue';
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
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as aiRequestsIndex } from '@/routes/ai-requests';
import { index as aiResponsesIndex } from '@/routes/ai-responses';
import { index as annotationSourcesIndex } from '@/routes/annotation-sources';
import { index as auditsIndex } from '@/routes/audits';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import { usePermissions } from '@/shared/hooks/use-permissions';
import type { NavItem } from '@/types';

const { canView } = usePermissions();

const mainNavItems = computed<NavItem[]>(() => [
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
    ...(canView('ai-responses')
        ? [
              {
                  title: 'AI responses',
                  href: aiResponsesIndex(),
                  icon: Bot,
              },
          ]
        : []),
    ...(canView('annotation-sources')
        ? [
              {
                  title: 'Annotation sources',
                  href: annotationSourcesIndex(),
                  icon: Database,
              },
          ]
        : []),
    ...(canView('users')
        ? [
              {
                  title: 'Users',
                  href: usersIndex(),
                  icon: Users,
              },
          ]
        : []),
    ...(canView('roles')
        ? [
              {
                  title: 'Roles',
                  href: rolesIndex(),
                  icon: Shield,
              },
          ]
        : []),
    ...(canView('audits')
        ? [
              {
                  title: 'Audits',
                  href: auditsIndex(),
                  icon: ClipboardList,
              },
          ]
        : []),
    ...(canView('activity-logs')
        ? [
              {
                  title: 'Activity logs',
                  href: activityLogsIndex(),
                  icon: Activity,
              },
          ]
        : []),
]);
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
