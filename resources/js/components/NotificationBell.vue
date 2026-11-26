<script setup lang="ts">
import { router, usePage, usePoll } from '@inertiajs/vue3';
import { Bell, CheckCheck, MessageSquare } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslation } from '@/composables/useTranslation';

type NotificationItem = {
    id: string;
    type: string;
    title: string;
    body: string;
    url: string;
    read_at: string | null;
    created_at: string | null;
};

type NotificationsPayload = {
    unread_count: number;
    items: NotificationItem[];
};

const page = usePage();
const { t } = useTranslation();

usePoll(10000, {
    only: ['notifications'],
});

const notifications = computed<NotificationsPayload>(() => {
    const raw = page.props.notifications as NotificationsPayload | undefined;

    return raw ?? { unread_count: 0, items: [] };
});

const openNotification = (item: NotificationItem) => {
    router.post(
        `/app/notifications/${encodeURIComponent(item.id)}/read`,
        {},
        { preserveScroll: true },
    );
};

const markAllAsRead = () => {
    router.post('/app/notifications/read-all', {}, { preserveScroll: true });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative size-8"
                :aria-label="t('notifications.title')"
            >
                <Bell class="size-4" />
                <span
                    v-if="notifications.unread_count > 0"
                    class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-semibold text-white"
                >
                    {{
                        notifications.unread_count > 99
                            ? '99+'
                            : notifications.unread_count
                    }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between gap-2">
                <span>{{ t('notifications.title') }}</span>
                <button
                    v-if="notifications.unread_count > 0"
                    type="button"
                    class="inline-flex items-center gap-1 text-xs font-normal text-primary hover:underline"
                    @click.stop="markAllAsRead"
                >
                    <CheckCheck class="size-3.5" />
                    {{ t('notifications.mark_all_read') }}
                </button>
            </DropdownMenuLabel>

            <DropdownMenuSeparator />

            <div
                v-if="notifications.items.length === 0"
                class="px-3 py-6 text-center text-xs text-muted-foreground"
            >
                {{ t('notifications.empty') }}
            </div>

            <div v-else class="max-h-80 overflow-y-auto">
                <DropdownMenuItem
                    v-for="item in notifications.items"
                    :key="item.id"
                    class="flex cursor-pointer items-start gap-2.5 px-3 py-2.5"
                    :class="!item.read_at ? 'bg-muted/50' : ''"
                    @click="openNotification(item)"
                >
                    <MessageSquare
                        class="mt-0.5 size-4 shrink-0 text-primary"
                    />
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-xs font-semibold">
                                {{ item.title }}
                            </p>
                            <span
                                v-if="!item.read_at"
                                class="size-2 shrink-0 rounded-full bg-primary"
                            />
                        </div>
                        <p class="line-clamp-2 text-xs text-muted-foreground">
                            {{ item.body }}
                        </p>
                        <p class="text-[10px] text-muted-foreground/80">
                            {{ item.created_at }}
                        </p>
                    </div>
                </DropdownMenuItem>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
