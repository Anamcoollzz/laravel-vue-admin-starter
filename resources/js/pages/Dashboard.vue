<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Info,
    Megaphone,
    OctagonAlert,
} from '@lucide/vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/composables/useTranslation';
import { dashboard } from '@/routes';

type DashboardAnnouncement = {
    id: number;
    title: string;
    content: string;
    type: 'info' | 'success' | 'warning' | 'danger';
    author: string | null;
    created_at: string | null;
};

withDefaults(
    defineProps<{
        announcements?: DashboardAnnouncement[];
    }>(),
    {
        announcements: () => [],
    },
);

const { t } = useTranslation();

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

const cardStyle = (type: DashboardAnnouncement['type']): string => {
    switch (type) {
        case 'success':
            return 'border-emerald-500/30 bg-emerald-500/5';
        case 'warning':
            return 'border-amber-500/30 bg-amber-500/5';
        case 'danger':
            return 'border-red-500/30 bg-red-500/5';
        default:
            return 'border-sky-500/30 bg-sky-500/5';
    }
};

const iconFor = (type: DashboardAnnouncement['type']) => {
    switch (type) {
        case 'success':
            return CheckCircle2;
        case 'warning':
            return AlertTriangle;
        case 'danger':
            return OctagonAlert;
        default:
            return Info;
    }
};
</script>

<template>
    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <Head :title="t('nav.dashboard')" />

        <!-- Active Announcements -->
        <div v-if="announcements.length > 0" class="space-y-3">
            <div class="flex items-center gap-2 text-sm font-medium">
                <Megaphone class="size-4 text-primary" />
                <span>{{ t('announcements.title') }}</span>
            </div>

            <div class="grid gap-3">
                <div
                    v-for="item in announcements"
                    :key="item.id"
                    class="rounded-xl border p-4"
                    :class="cardStyle(item.type)"
                >
                    <div class="flex items-start gap-3">
                        <component
                            :is="iconFor(item.type)"
                            class="mt-0.5 size-5 shrink-0"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <h2 class="font-semibold">{{ item.title }}</h2>
                                <Badge variant="outline" class="uppercase">
                                    {{ item.type }}
                                </Badge>
                            </div>
                            <p
                                class="text-sm whitespace-pre-line text-muted-foreground"
                            >
                                {{ item.content }}
                            </p>
                            <p class="text-xs text-muted-foreground/80">
                                <span v-if="item.author">{{
                                    item.author
                                }}</span>
                                <span v-if="item.author && item.created_at">
                                    ·
                                </span>
                                <span v-if="item.created_at">
                                    {{ item.created_at }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Head :title="t('nav.dashboard')" />

        <!-- Active Announcements -->
        <div v-if="announcements.length > 0" class="space-y-3">
            <div class="flex items-center gap-2 text-sm font-medium">
                <Megaphone class="size-4 text-primary" />
                <span>{{ t('announcements.title') }}</span>
            </div>

            <div class="grid gap-3">
                <div
                    v-for="item in announcements"
                    :key="item.id"
                    class="rounded-xl border p-4"
                    :class="cardStyle(item.type)"
                >
                    <div class="flex items-start gap-3">
                        <component
                            :is="iconFor(item.type)"
                            class="mt-0.5 size-5 shrink-0"
                        />
                        <div class="min-w-0 flex-1 space-y-1">
                            <div
                                class="flex flex-wrap items-center justify-between gap-2"
                            >
                                <h2 class="font-semibold">{{ item.title }}</h2>
                                <Badge variant="outline" class="uppercase">
                                    {{ item.type }}
                                </Badge>
                            </div>
                            <p
                                class="whitespace-pre-line text-sm text-muted-foreground"
                            >
                                {{ item.content }}
                            </p>
                            <p class="text-xs text-muted-foreground/80">
                                <span v-if="item.author">{{ item.author }}</span>
                                <span v-if="item.author && item.created_at">
                                    ·
                                </span>
                                <span v-if="item.created_at">
                                    {{ item.created_at }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
