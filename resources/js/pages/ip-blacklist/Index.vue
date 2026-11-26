<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, ShieldBan, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

type IpBlacklistRow = {
    id: number;
    ip_address: string;
    reason: string | null;
    is_active: boolean;
    expires_at: string | null;
    expires_at_display: string | null;
    creator: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedEntries = {
    data: IpBlacklistRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    entries: PaginatedEntries;
    currentIp: string | null;
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
                title: 'IP Blacklist',
                href: '/app/ip-blacklist',
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
            '/app/ip-blacklist',
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const isDialogOpen = ref(false);
const editingItem = ref<IpBlacklistRow | null>(null);

const form = useForm({
    ip_address: '',
    reason: '',
    is_active: true,
    expires_at: '',
});

const openCreateDialog = () => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    isDialogOpen.value = true;
};

const openEditDialog = (item: IpBlacklistRow) => {
    editingItem.value = item;
    form.reset();
    form.clearErrors();
    form.ip_address = item.ip_address;
    form.reason = item.reason ?? '';
    form.is_active = item.is_active;
    form.expires_at = item.expires_at ?? '';
    isDialogOpen.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/app/ip-blacklist/${editingItem.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        form.post('/app/ip-blacklist', {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

const deleteItem = (item: IpBlacklistRow) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(`/app/ip-blacklist/${item.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('ip_blacklist.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ t('ip_blacklist.title') }}
                    </h1>
                    <Badge v-if="currentIp" variant="outline" class="font-mono">
                        {{ t('ip_blacklist.your_ip') }}: {{ currentIp }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t('ip_blacklist.description') }}
                </p>
            </div>

            <Button v-if="can('ip_blacklist.create')" @click="openCreateDialog">
                <Plus class="mr-2 size-4" />
                {{ t('ip_blacklist.add') }}
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
                    :placeholder="t('ip_blacklist.search_placeholder')"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('ip_blacklist.ip_address') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('ip_blacklist.reason') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('announcements.field_status') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('ip_blacklist.expires_at') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="item in entries.data"
                            :key="item.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                <div class="flex items-center gap-2">
                                    <ShieldBan
                                        class="size-4 text-destructive"
                                    />
                                    {{ item.ip_address }}
                                </div>
                            </td>
                            <td
                                class="max-w-sm px-4 py-3 text-muted-foreground"
                            >
                                {{ item.reason ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        item.is_active
                                            ? 'destructive'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        item.is_active
                                            ? t('announcements.active')
                                            : t('announcements.inactive')
                                    }}
                                </Badge>
                            </td>
                            <td
                                class="px-4 py-3 text-xs whitespace-nowrap text-muted-foreground"
                            >
                                {{
                                    item.expires_at_display ??
                                    t('ip_blacklist.permanent')
                                }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        v-if="can('ip_blacklist.edit')"
                                        variant="ghost"
                                        size="icon"
                                        :title="t('common.edit')"
                                        @click="openEditDialog(item)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="can('ip_blacklist.delete')"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :title="t('common.delete')"
                                        @click="deleteItem(item)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="entries.data.length === 0">
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

        <Dialog v-model:open="isDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingItem
                                ? t('ip_blacklist.edit')
                                : t('ip_blacklist.add')
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editingItem
                                ? t('ip_blacklist.edit_desc')
                                : t('ip_blacklist.create_desc')
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="ip-address">
                            {{ t('ip_blacklist.ip_address') }}
                        </Label>
                        <Input
                            id="ip-address"
                            v-model="form.ip_address"
                            type="text"
                            class="font-mono"
                            placeholder="192.168.1.100"
                            required
                        />
                        <InputError :message="form.errors.ip_address" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ip-reason">
                            {{ t('ip_blacklist.reason') }}
                        </Label>
                        <Input
                            id="ip-reason"
                            v-model="form.reason"
                            type="text"
                        />
                        <InputError :message="form.errors.reason" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="ip-expires">
                            {{ t('ip_blacklist.expires_at') }}
                        </Label>
                        <Input
                            id="ip-expires"
                            v-model="form.expires_at"
                            type="datetime-local"
                        />
                        <InputError :message="form.errors.expires_at" />
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input accent-primary"
                        />
                        <span>{{ t('ip_blacklist.active_label') }}</span>
                    </label>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isDialogOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? t('common.saving')
                                    : t('common.save')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
