<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MonitorSmartphone, Search, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

type LoginHistoryRow = {
    id: number;
    user_name: string | null;
    email: string | null;
    status: 'success' | 'failed' | 'logout';
    ip_address: string | null;
    browser: string | null;
    platform: string | null;
    created_at: string | null;
    created_at_human: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedHistories = {
    data: LoginHistoryRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    histories: PaginatedHistories;
    filters: {
        search: string;
        status: string;
    };
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Login History',
                href: '/app/login-history',
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/app/login-history',
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
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

watch(statusFilter, applyFilters);

const deleteEntry = (id: number) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(`/app/login-history/${id}`, {
        preserveScroll: true,
    });
};

const clearAll = () => {
    if (!window.confirm(t('login_history.confirm_clear'))) {
        return;
    }

    router.delete('/app/login-history/clear', {
        preserveScroll: true,
    });
};

const statusBadgeClass = (status: LoginHistoryRow['status']): string => {
    switch (status) {
        case 'success':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        case 'failed':
            return 'bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30';
        default:
            return 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30';
    }
};

const statusLabel = (status: LoginHistoryRow['status']): string => {
    switch (status) {
        case 'success':
            return t('login_history.status_success');
        case 'failed':
            return t('login_history.status_failed');
        default:
            return t('login_history.status_logout');
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('login_history.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('login_history.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('login_history.description') }}
                </p>
            </div>

            <Button
                v-if="can('login_history.delete') && histories.total > 0"
                variant="destructive"
                @click="clearAll"
            >
                <Trash2 class="mr-2 size-4" />
                {{ t('login_history.clear_all') }}
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
                    :placeholder="t('login_history.search_placeholder')"
                />
            </div>

            <select
                v-model="statusFilter"
                class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
            >
                <option value="">{{ t('login_history.all_statuses') }}</option>
                <option value="success">
                    {{ t('login_history.status_success') }}
                </option>
                <option value="failed">
                    {{ t('login_history.status_failed') }}
                </option>
                <option value="logout">
                    {{ t('login_history.status_logout') }}
                </option>
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
                                {{ t('logs.status') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('sessions.device') }}
                            </th>
                            <th class="px-4 py-3 font-medium">IP</th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="item in histories.data"
                            :key="item.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 text-xs whitespace-nowrap">
                                <div>{{ item.created_at_human }}</div>
                                <div class="font-mono text-muted-foreground">
                                    {{ item.created_at }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ item.user_name ?? t('logs.guest') }}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.email ?? '-' }}
                                </p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge
                                    variant="outline"
                                    :class="statusBadgeClass(item.status)"
                                >
                                    {{ statusLabel(item.status) }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <MonitorSmartphone
                                        class="size-4 text-muted-foreground"
                                    />
                                    <span>
                                        {{ item.browser ?? '-' }} ·
                                        {{ item.platform ?? '-' }}
                                    </span>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs whitespace-nowrap"
                            >
                                {{ item.ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <Button
                                    v-if="can('login_history.delete')"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    :title="t('common.delete')"
                                    @click="deleteEntry(item.id)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="histories.data.length === 0">
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
                v-if="histories.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t('common.showing') }} {{ histories.from }} -
                    {{ histories.to }} {{ t('common.of') }}
                    {{ histories.total }} {{ t('common.entries') }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="link in histories.links" :key="link.label">
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
    </div>
</template>
