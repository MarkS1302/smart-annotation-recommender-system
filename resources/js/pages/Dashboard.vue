<script setup lang="ts">
import { Head, Link, router, usePoll } from '@inertiajs/vue3';
import {
    Activity,
    Bot,
    ClipboardList,
    RefreshCw,
    Shield,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { index as aiRequestsIndex } from '@/routes/ai-requests';
import { show as aiResponsesShow } from '@/routes/ai-responses';
import { index as auditsIndex } from '@/routes/audits';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import { usePermissions } from '@/shared/hooks/use-permissions';

const { canView } = usePermissions();
const isRefreshingAiRequests = ref(false);

type AiRequest = {
    id: number;
    requestId: string;
    entity: string;
    status: 'pending' | 'success' | 'error';
    createdAt: string | null;
};

defineProps<{
    aiRequests: AiRequest[];
}>();

usePoll(10000, {
    only: ['aiRequests'],
});

function requestUrl(id: number): string {
    return aiResponsesShow.url(id);
}

function refreshAiRequests(): void {
    router.reload({
        only: ['aiRequests'],
        preserveScroll: true,
        onStart: () => {
            isRefreshingAiRequests.value = true;
        },
        onFinish: () => {
            isRefreshingAiRequests.value = false;
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Link
                :href="aiRequestsIndex()"
                class="block rounded-xl border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-center gap-3">
                    <Bot class="size-5" />
                    <div>
                        <p class="text-sm font-medium">AI requests</p>
                        <p class="text-xs text-muted-foreground">
                            {{
                                aiRequests.filter(
                                    (request) => request.status === 'pending',
                                ).length
                            }}
                            thinking now
                        </p>
                    </div>
                </div>
            </Link>
            <Link
                v-if="canView('users')"
                :href="usersIndex()"
                class="block rounded-xl border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-center gap-3">
                    <Users class="size-5" />
                    <div>
                        <p class="text-sm font-medium">Users</p>
                        <p class="text-xs text-muted-foreground">
                            Manage accounts
                        </p>
                    </div>
                </div>
            </Link>

            <Link
                v-if="canView('roles')"
                :href="rolesIndex()"
                class="block rounded-xl border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-center gap-3">
                    <Shield class="size-5" />
                    <div>
                        <p class="text-sm font-medium">Roles</p>
                        <p class="text-xs text-muted-foreground">
                            Manage access
                        </p>
                    </div>
                </div>
            </Link>

            <Link
                v-if="canView('audits')"
                :href="auditsIndex()"
                class="block rounded-xl border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-center gap-3">
                    <ClipboardList class="size-5" />
                    <div>
                        <p class="text-sm font-medium">Audits</p>
                        <p class="text-xs text-muted-foreground">
                            Review changes
                        </p>
                    </div>
                </div>
            </Link>

            <Link
                v-if="canView('activity-logs')"
                :href="activityLogsIndex()"
                class="block rounded-xl border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex items-center gap-3">
                    <Activity class="size-5" />
                    <div>
                        <p class="text-sm font-medium">Activity</p>
                        <p class="text-xs text-muted-foreground">
                            Inspect log trail
                        </p>
                    </div>
                </div>
            </Link>
        </div>

        <section class="rounded-xl border p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-medium">AI request activity</h2>
                    <p class="text-sm text-muted-foreground">
                        Requests keep running while you start another one.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        type="button"
                        :disabled="isRefreshingAiRequests"
                        @click="refreshAiRequests"
                    >
                        <RefreshCw
                            class="size-4"
                            :class="{ 'animate-spin': isRefreshingAiRequests }"
                        />
                        Refresh
                    </Button>

                    <Button as-child size="sm">
                        <Link :href="aiRequestsIndex()">New AI request</Link>
                    </Button>
                </div>
            </div>

            <div
                v-if="aiRequests.length"
                class="mt-4 divide-y rounded-lg border"
            >
                <Link
                    v-for="request in aiRequests"
                    :key="request.requestId"
                    :href="requestUrl(request.id)"
                    class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-muted/50"
                >
                    <div class="min-w-0">
                        <p class="font-medium capitalize">
                            {{ request.entity }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{
                                request.createdAt
                                    ? new Date(
                                          request.createdAt,
                                      ).toLocaleString()
                                    : 'Just now'
                            }}
                        </p>
                    </div>
                    <Badge
                        :variant="
                            request.status === 'error'
                                ? 'destructive'
                                : request.status === 'pending'
                                  ? 'secondary'
                                  : 'default'
                        "
                    >
                        {{ request.status }}
                    </Badge>
                </Link>
            </div>

            <p v-else class="mt-4 text-sm text-muted-foreground">
                No AI requests yet.
            </p>
        </section>
    </div>
</template>
