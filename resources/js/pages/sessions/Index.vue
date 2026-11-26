<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { LogOut, MonitorSmartphone, Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

type SessionRow = {
    id: string;
    user_id: number | null;
    user_name: string | null;
    user_email: string | null;
    ip_address: string | null;
    browser: string;
    platform: string;
    is_current: boolean;
    last_activity: string;
    last_activity_human: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedSessions = {
    data: SessionRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    sessions: PaginatedSessions;
    driver: string;
    filters: {
        search: string;
    };
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'User Sessions',
                href: '/app/sessions',
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/app/sessions',
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const revokeSession = (session: SessionRow) => {
    if (!window.confirm(t('sessions.confirm_revoke'))) {
        return;
    }

    router.delete(`/app/sessions/${encodeURIComponent(session.id)}`, {
        preserveScroll: true,
    });
};

const revokeOthers = () => {
    if (!window.confirm(t('sessions.confirm_revoke_others'))) {
        return;
    }

    router.delete('/app/sessions/others', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('sessions.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ t('sessions.title') }}
                    </h1>
                    <Badge variant="outline" class="uppercase">
                        {{ driver }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t('sessions.description') }}
                </p>
            </div>

            <Button
                v-if="can('sessions.delete') && sessions.total > 1"
                variant="destructive"
                @click="revokeOthers"
            >
                <LogOut class="mr-2 size-4" />
                {{ t('sessions.revoke_others') }}
            </Button>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative w-full max-w-sm">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    :placeholder="t('sessions.search_placeholder')"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('logs.user') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('sessions.device') }}
                            </th>
                            <th class="px-4 py-3 font-medium">IP</th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('sessions.last_activity') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="session in sessions.data"
                            :key="session.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">
                                        {{ session.user_name ?? '-' }}
                                    </span>
                                    <Badge
                                        v-if="session.is_current"
                                        variant="default"
                                        class="text-[10px]"
                                    >
                                        {{ t('sessions.current') }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ session.user_email ?? '-' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <MonitorSmartphone
                                        class="size-4 text-muted-foreground"
                                    />
                                    <span>
                                        {{ session.browser }} ·
                                        {{ session.platform }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                {{ session.ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <div>{{ session.last_activity_human }}</div>
                                <div class="font-mono text-muted-foreground">
                                    {{ session.last_activity }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    v-if="
                                        can('sessions.delete') &&
                                        !session.is_current
                                    "
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="revokeSession(session)"
                                >
                                    <LogOut class="mr-1.5 size-4" />
                                    {{ t('sessions.revoke') }}
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="sessions.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="sessions.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t('common.showing') }} {{ sessions.from }} -
                    {{ sessions.to }} {{ t('common.of') }} {{ sessions.total }}
                    {{ t('common.entries') }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="link in sessions.links" :key="link.label">
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
