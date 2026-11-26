<script setup lang="ts">
import { Head, Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { Check, CheckCheck, MessageSquare, Search, Send } from '@lucide/vue';
import { nextTick, onMounted, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/composables/useInitials';
import { useTranslation } from '@/composables/useTranslation';

type ContactItem = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    unread_count: number;
    last_message: string | null;
    last_message_at: string | null;
};

type ActivePeer = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
};

type MessageItem = {
    id: number;
    sender_id: number;
    receiver_id: number;
    body: string;
    is_mine: boolean;
    is_read: boolean;
    created_at: string | null;
};

const props = defineProps<{
    contacts: ContactItem[];
    activeUser: ActivePeer | null;
    messages: MessageItem[];
    filters: {
        search: string;
    };
}>();

const { t } = useTranslation();
const { getInitials } = useInitials();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Messages',
                href: '/app/messages',
            },
        ],
    },
});

usePoll(5000, {
    only: ['contacts', 'messages'],
});

const search = ref(props.filters.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/app/messages',
            {
                user: props.activeUser?.id || undefined,
                search: value || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const messageContainer = ref<HTMLElement | null>(null);

const scrollToBottom = async () => {
    await nextTick();

    if (messageContainer.value) {
        messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
    }
};

onMounted(scrollToBottom);
watch(() => props.messages.length, scrollToBottom);
watch(() => props.activeUser?.id, scrollToBottom);

const form = useForm({
    receiver_id: props.activeUser?.id ?? 0,
    body: '',
});

const sendMessage = () => {
    if (!props.activeUser || !form.body.trim()) {
        return;
    }

    form.receiver_id = props.activeUser.id;
    form.post('/app/messages', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('body');
            scrollToBottom();
        },
    });
};
</script>

<template>
    <div class="flex h-[calc(100vh-5rem)] flex-1 flex-col p-4 md:p-6">
        <Head :title="t('messages.title')" />

        <div
            class="grid min-h-0 flex-1 grid-cols-1 overflow-hidden rounded-xl border border-border bg-card md:grid-cols-12"
        >
            <!-- Contacts Sidebar -->
            <aside
                class="flex min-h-0 flex-col border-b border-border md:col-span-4 md:border-r md:border-b-0 lg:col-span-3"
            >
                <div class="border-b border-border p-3">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            class="pl-9"
                            :placeholder="t('messages.search_users')"
                        />
                    </div>
                </div>

                <div class="flex-1 divide-y divide-border overflow-y-auto">
                    <Link
                        v-for="contact in contacts"
                        :key="contact.id"
                        :href="`/app/messages?user=${contact.id}`"
                        preserve-scroll
                        class="flex items-center gap-3 px-3 py-3 transition-colors hover:bg-muted/50"
                        :class="
                            activeUser?.id === contact.id ? 'bg-muted/70' : ''
                        "
                    >
                        <Avatar class="size-10 shrink-0 rounded-full">
                            <AvatarImage
                                v-if="contact.avatar"
                                :src="contact.avatar"
                                :alt="contact.name"
                            />
                            <AvatarFallback class="rounded-full text-xs">
                                {{ getInitials(contact.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="min-w-0 flex-1">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p class="truncate text-sm font-medium">
                                    {{ contact.name }}
                                </p>
                                <span
                                    v-if="contact.last_message_at"
                                    class="shrink-0 text-[11px] text-muted-foreground"
                                >
                                    {{ contact.last_message_at }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ contact.last_message ?? contact.email }}
                                </p>
                                <Badge
                                    v-if="contact.unread_count > 0"
                                    variant="default"
                                    class="h-5 min-w-5 justify-center rounded-full px-1.5 text-[10px]"
                                >
                                    {{ contact.unread_count }}
                                </Badge>
                            </div>
                        </div>
                    </Link>

                    <div
                        v-if="contacts.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('messages.no_contacts') }}
                    </div>
                </div>
            </aside>

            <!-- Chat Area -->
            <section class="flex min-h-0 flex-col md:col-span-8 lg:col-span-9">
                <template v-if="activeUser">
                    <!-- Chat Header -->
                    <div
                        class="flex items-center gap-3 border-b border-border px-4 py-3"
                    >
                        <Avatar class="size-9 rounded-full">
                            <AvatarImage
                                v-if="activeUser.avatar"
                                :src="activeUser.avatar"
                                :alt="activeUser.name"
                            />
                            <AvatarFallback class="rounded-full text-xs">
                                {{ getInitials(activeUser.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                {{ activeUser.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ activeUser.email }}
                            </p>
                        </div>
                    </div>

                    <!-- Message Bubbles -->
                    <div
                        ref="messageContainer"
                        class="flex-1 space-y-3 overflow-y-auto p-4"
                    >
                        <div
                            v-for="msg in messages"
                            :key="msg.id"
                            class="flex"
                            :class="
                                msg.is_mine ? 'justify-end' : 'justify-start'
                            "
                        >
                            <div
                                class="max-w-[75%] rounded-2xl px-4 py-2.5 text-sm shadow-xs"
                                :class="
                                    msg.is_mine
                                        ? 'rounded-br-sm bg-primary text-primary-foreground'
                                        : 'rounded-bl-sm bg-muted text-foreground'
                                "
                            >
                                <p class="break-words whitespace-pre-wrap">
                                    {{ msg.body }}
                                </p>
                                <div
                                    class="mt-1 flex items-center justify-end gap-1 text-[10px] opacity-75"
                                >
                                    <span>{{ msg.created_at }}</span>
                                    <template v-if="msg.is_mine">
                                        <CheckCheck
                                            v-if="msg.is_read"
                                            class="size-3"
                                        />
                                        <Check v-else class="size-3" />
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="messages.length === 0"
                            class="flex h-full items-center justify-center text-sm text-muted-foreground"
                        >
                            {{ t('messages.no_messages') }}
                        </div>
                    </div>

                    <!-- Input Form -->
                    <form
                        class="flex items-center gap-2 border-t border-border p-3"
                        @submit.prevent="sendMessage"
                    >
                        <Input
                            v-model="form.body"
                            type="text"
                            class="flex-1"
                            :placeholder="t('messages.type_placeholder')"
                            autocomplete="off"
                        />
                        <Button
                            type="submit"
                            :disabled="form.processing || !form.body.trim()"
                        >
                            <Send class="mr-1.5 size-4" />
                            {{ t('messages.send') }}
                        </Button>
                    </form>
                </template>

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-center text-muted-foreground"
                >
                    <MessageSquare class="size-10 opacity-40" />
                    <p class="text-sm">
                        {{ t('messages.select_conversation') }}
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
