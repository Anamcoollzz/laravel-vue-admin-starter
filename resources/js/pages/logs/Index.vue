<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Search, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

type RequestLogRow = {
    id: number;
    user_id: number | null;
    user: { name: string; email: string } | null;
    roles: string[];
    ip_address: string | null;
    method: string;
    path: string;
    route_name: string | null;
    status_code: number | null;
    duration_ms: number | null;
    browser: string | null;
    platform: string | null;
    user_agent: string | null;
    request_payload: Record<string, unknown> | null;
    referer: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedLogs = {
    data: RequestLogRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    logs: PaginatedLogs;
    filters: {
        search: string;
        method: string;
    };
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Request Logs',
                href: '/app/request-logs',
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const methodFilter = ref(props.filters.method ?? '');
let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/app/request-logs',
        {
            search: search.value || undefined,
            method: methodFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }

    filterTimeout = setTimeout(applyFilters, 300);
});

watch(methodFilter, applyFilters);

const selectedLog = ref<RequestLogRow | null>(null);
const isDetailOpen = ref(false);

const openDetail = (log: RequestLogRow) => {
    selectedLog.value = log;
    isDetailOpen.value = true;
};

const deleteLog = (id: number) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(`/app/request-logs/${id}`, {
        preserveScroll: true,
    });
};

const clearAll = () => {
    if (!window.confirm(t('logs.confirm_clear'))) {
        return;
    }

    router.delete('/app/request-logs/clear', {
        preserveScroll: true,
    });
};

const methodBadgeClass = (method: string): string => {
    switch (method) {
        case 'GET':
            return 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30';
        case 'POST':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        case 'PUT':
        case 'PATCH':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
        case 'DELETE':
            return 'bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30';
        default:
            return '';
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('logs.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('logs.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('logs.description') }}
                </p>
            </div>

            <Button
                v-if="can('logs.delete') && logs.total > 0"
                variant="destructive"
                @click="clearAll"
            >
                <Trash2 class="mr-2 size-4" />
                {{ t('logs.clear_all') }}
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative w-full max-w-sm">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    :placeholder="t('logs.search_placeholder')"
                />
            </div>

            <select
                v-model="methodFilter"
                class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
            >
                <option value="">{{ t('logs.all_methods') }}</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
                <option value="DELETE">DELETE</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('logs.time') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('logs.user') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('logs.method') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('logs.path') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('logs.ip_browser') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-3 font-mono text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{ log.created_at }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ log.user?.name ?? t('logs.guest') }}
                                </div>
                                <div
                                    v-if="log.roles.length > 0"
                                    class="mt-0.5 flex flex-wrap gap-1"
                                >
                                    <Badge
                                        v-for="role in log.roles"
                                        :key="role"
                                        variant="secondary"
                                        class="px-1.5 py-0 text-[10px]"
                                    >
                                        {{ role }}
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge
                                    variant="outline"
                                    class="font-mono text-xs"
                                    :class="methodBadgeClass(log.method)"
                                >
                                    {{ log.method }}
                                </Badge>
                            </td>
                            <td class="max-w-xs px-4 py-3">
                                <div class="truncate font-mono text-xs">
                                    {{ log.path }}
                                </div>
                                <div
                                    class="mt-0.5 flex items-center gap-2 text-[11px] text-muted-foreground"
                                >
                                    <span v-if="log.status_code">
                                        HTTP {{ log.status_code }}
                                    </span>
                                    <span v-if="log.duration_ms !== null">
                                        {{ log.duration_ms }}ms
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                <div class="font-mono">
                                    {{ log.ip_address ?? '-' }}
                                </div>
                                <div class="text-muted-foreground">
                                    {{ log.browser ?? '-' }}
                                    <span v-if="log.platform">
                                        · {{ log.platform }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :title="t('logs.detail')"
                                        @click="openDetail(log)"
                                    >
                                        <Eye class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="can('logs.delete')"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :title="t('common.delete')"
                                        @click="deleteLog(log.id)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="logs.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t('common.showing') }} {{ logs.from }} - {{ logs.to }}
                    {{ t('common.of') }} {{ logs.total }}
                    {{ t('common.entries') }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="link in logs.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="rounded-md border px-2.5 py-1 text-xs transition-colors"
                            :class="
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border hover:bg-muted'
                            "
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            class="rounded-md border border-border/50 px-2.5 py-1 text-xs text-muted-foreground opacity-50"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Detail Dialog -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ t('logs.detail') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('logs.detail_desc') }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedLog" class="space-y-3 text-sm">
                    <div
                        class="grid grid-cols-2 gap-3 rounded-lg border border-border p-3"
                    >
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('logs.method') }} & {{ t('logs.status') }}
                            </p>
                            <p class="font-mono font-medium">
                                {{ selectedLog.method }} · HTTP
                                {{ selectedLog.status_code ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('logs.duration') }}
                            </p>
                            <p class="font-mono">
                                {{ selectedLog.duration_ms ?? 0 }} ms
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('logs.path') }}
                            </p>
                            <p class="font-mono text-xs break-all">
                                {{ selectedLog.path }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('logs.route') }}
                            </p>
                            <p class="font-mono text-xs">
                                {{ selectedLog.route_name ?? '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('logs.user') }}
                            </p>
                            <p>
                                {{ selectedLog.user?.name ?? t('logs.guest') }}
                                <span
                                    v-if="selectedLog.user_id"
                                    class="text-xs text-muted-foreground"
                                >
                                    (#{{ selectedLog.user_id }})
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('logs.ip_browser') }}
                            </p>
                            <p class="font-mono text-xs">
                                {{ selectedLog.ip_address ?? '-' }} ·
                                {{ selectedLog.browser ?? '-' }} /
                                {{ selectedLog.platform ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            {{ t('logs.user_agent') }}
                        </p>
                        <p
                            class="rounded-md bg-muted p-2 font-mono text-xs break-all"
                        >
                            {{ selectedLog.user_agent ?? '-' }}
                        </p>
                    </div>

                    <div v-if="selectedLog.referer">
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            {{ t('logs.referer') }}
                        </p>
                        <p
                            class="rounded-md bg-muted p-2 font-mono text-xs break-all"
                        >
                            {{ selectedLog.referer }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            {{ t('logs.payload') }}
                        </p>
                        <pre
                            v-if="selectedLog.request_payload"
                            class="max-h-60 overflow-auto rounded-md bg-muted p-3 font-mono text-xs"
                            >{{
                                JSON.stringify(
                                    selectedLog.request_payload,
                                    null,
                                    2,
                                )
                            }}</pre>
                        <p v-else class="text-xs text-muted-foreground">
                            {{ t('logs.no_payload') }}
                        </p>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
