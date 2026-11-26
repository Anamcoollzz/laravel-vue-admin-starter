<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Eraser, Eye, Search, Trash2 } from '@lucide/vue';
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

type LogFile = {
    name: string;
    size: string;
    last_modified: string;
};

type LogEntry = {
    id: number;
    datetime: string;
    env: string;
    level: string;
    message: string;
    stack: string;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedEntries = {
    data: LogEntry[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    files: LogFile[];
    selectedFile: string;
    entries: PaginatedEntries;
    filters: {
        level: string;
        search: string;
    };
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Laravel Log Viewer',
                href: '/app/laravel-logs',
            },
        ],
    },
});

const fileFilter = ref(props.selectedFile);
const levelFilter = ref(props.filters.level ?? '');
const search = ref(props.filters.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/app/laravel-logs',
        {
            file: fileFilter.value || undefined,
            level: levelFilter.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(fileFilter, applyFilters);
watch(levelFilter, applyFilters);
watch(search, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(applyFilters, 300);
});

const selectedEntry = ref<LogEntry | null>(null);
const isDetailOpen = ref(false);

const openDetail = (entry: LogEntry) => {
    selectedEntry.value = entry;
    isDetailOpen.value = true;
};

const clearLog = () => {
    if (
        !props.selectedFile ||
        !window.confirm(t('laravel_logs.confirm_clear'))
    ) {
        return;
    }

    router.post(
        '/app/laravel-logs/clear',
        { file: props.selectedFile },
        { preserveScroll: true },
    );
};

const deleteLogFile = () => {
    if (!props.selectedFile || !window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete('/app/laravel-logs', {
        data: { file: props.selectedFile },
        preserveScroll: true,
    });
};

const levelBadgeClass = (level: string): string => {
    switch (level) {
        case 'EMERGENCY':
        case 'ALERT':
        case 'CRITICAL':
        case 'ERROR':
            return 'bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30';
        case 'WARNING':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
        case 'NOTICE':
        case 'INFO':
            return 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30';
        case 'DEBUG':
            return 'bg-slate-500/15 text-slate-700 dark:text-slate-300 border-slate-500/30';
        default:
            return '';
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('laravel_logs.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('laravel_logs.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('laravel_logs.description') }}
                </p>
            </div>

            <div v-if="selectedFile" class="flex flex-wrap items-center gap-2">
                <Button variant="outline" size="sm" as-child>
                    <a
                        :href="`/app/laravel-logs/download?file=${encodeURIComponent(selectedFile)}`"
                    >
                        <Download class="mr-1.5 size-4" />
                        {{ t('laravel_logs.download') }}
                    </a>
                </Button>
                <Button
                    v-if="can('logs.delete')"
                    variant="outline"
                    size="sm"
                    @click="clearLog"
                >
                    <Eraser class="mr-1.5 size-4" />
                    {{ t('laravel_logs.clear') }}
                </Button>
                <Button
                    v-if="can('logs.delete')"
                    variant="destructive"
                    size="sm"
                    @click="deleteLogFile"
                >
                    <Trash2 class="mr-1.5 size-4" />
                    {{ t('common.delete') }}
                </Button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <select
                v-if="files.length > 0"
                v-model="fileFilter"
                class="flex h-9 rounded-md border border-input bg-background px-3 py-1 font-mono text-xs shadow-xs"
            >
                <option
                    v-for="file in files"
                    :key="file.name"
                    :value="file.name"
                >
                    {{ file.name }} ({{ file.size }})
                </option>
            </select>

            <select
                v-model="levelFilter"
                class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
            >
                <option value="">{{ t('laravel_logs.all_levels') }}</option>
                <option value="ERROR">ERROR</option>
                <option value="CRITICAL">CRITICAL</option>
                <option value="WARNING">WARNING</option>
                <option value="NOTICE">NOTICE</option>
                <option value="INFO">INFO</option>
                <option value="DEBUG">DEBUG</option>
            </select>

            <div class="relative w-full max-w-sm">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="pl-9"
                    :placeholder="t('laravel_logs.search_placeholder')"
                />
            </div>
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
                                {{ t('laravel_logs.level') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('laravel_logs.env') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('laravel_logs.message') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="entry in entries.data"
                            :key="entry.id"
                            class="hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-3 font-mono text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{ entry.datetime }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <Badge
                                    variant="outline"
                                    class="font-mono text-xs"
                                    :class="levelBadgeClass(entry.level)"
                                >
                                    {{ entry.level }}
                                </Badge>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{ entry.env }}
                            </td>
                            <td class="max-w-xl px-4 py-3">
                                <p class="line-clamp-2 font-mono text-xs">
                                    {{ entry.message }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    :title="t('laravel_logs.view_detail')"
                                    @click="openDetail(entry)"
                                >
                                    <Eye class="size-4" />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="entries.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{
                                    files.length === 0
                                        ? t('laravel_logs.no_files')
                                        : t('laravel_logs.no_entries')
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="entries.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t('common.showing') }} {{ entries.from }} -
                    {{ entries.to }} {{ t('common.of') }} {{ entries.total }}
                    {{ t('common.entries') }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="link in entries.links" :key="link.label">
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

        <!-- Stack Trace Detail Dialog -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Badge
                            v-if="selectedEntry"
                            variant="outline"
                            class="font-mono text-xs"
                            :class="levelBadgeClass(selectedEntry.level)"
                        >
                            {{ selectedEntry.level }}
                        </Badge>
                        <span class="font-mono text-sm">
                            {{ selectedEntry?.datetime }}
                        </span>
                    </DialogTitle>
                    <DialogDescription>
                        {{ selectedFile }} · {{ selectedEntry?.env }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedEntry" class="space-y-4 text-sm">
                    <div>
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            {{ t('laravel_logs.message') }}
                        </p>
                        <pre
                            class="rounded-md bg-muted p-3 font-mono text-xs break-all whitespace-pre-wrap"
                            >{{ selectedEntry.message }}</pre>
                    </div>

                    <div v-if="selectedEntry.stack">
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            {{ t('laravel_logs.stack_trace') }}
                        </p>
                        <pre
                            class="max-h-96 overflow-auto rounded-md bg-muted p-3 font-mono text-xs break-all whitespace-pre-wrap"
                            >{{ selectedEntry.stack }}</pre>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
