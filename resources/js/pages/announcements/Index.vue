<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
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

type AnnouncementType = 'info' | 'success' | 'warning' | 'danger';

type AnnouncementRow = {
    id: number;
    title: string;
    content: string;
    type: AnnouncementType;
    is_active: boolean;
    published_at: string | null;
    expires_at: string | null;
    author: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedAnnouncements = {
    data: AnnouncementRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    announcements: PaginatedAnnouncements;
    filters: {
        search: string;
        type: string;
    };
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Announcements',
                href: '/app/announcements',
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const typeFilter = ref(props.filters.type ?? '');
let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    router.get(
        '/app/announcements',
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
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

watch(typeFilter, applyFilters);

const isDialogOpen = ref(false);
const editingItem = ref<AnnouncementRow | null>(null);

const form = useForm({
    title: '',
    content: '',
    type: 'info' as AnnouncementType,
    is_active: true,
    published_at: '',
    expires_at: '',
});

const openCreateDialog = () => {
    editingItem.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'info';
    form.is_active = true;
    isDialogOpen.value = true;
};

const openEditDialog = (item: AnnouncementRow) => {
    editingItem.value = item;
    form.reset();
    form.clearErrors();
    form.title = item.title;
    form.content = item.content;
    form.type = item.type;
    form.is_active = item.is_active;
    form.published_at = item.published_at ?? '';
    form.expires_at = item.expires_at ?? '';
    isDialogOpen.value = true;
};

const submitForm = () => {
    if (editingItem.value) {
        form.put(`/app/announcements/${editingItem.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        form.post('/app/announcements', {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

const deleteItem = (item: AnnouncementRow) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(`/app/announcements/${item.id}`, {
        preserveScroll: true,
    });
};

const typeBadgeClass = (type: string): string => {
    switch (type) {
        case 'success':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
        case 'warning':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
        case 'danger':
            return 'bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30';
        default:
            return 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30';
    }
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('announcements.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('announcements.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('announcements.description') }}
                </p>
            </div>

            <Button
                v-if="can('announcements.create')"
                @click="openCreateDialog"
            >
                <Plus class="mr-2 size-4" />
                {{ t('announcements.add') }}
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
                    :placeholder="t('announcements.search_placeholder')"
                />
            </div>

            <select
                v-model="typeFilter"
                class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
            >
                <option value="">{{ t('announcements.all_types') }}</option>
                <option value="info">Info</option>
                <option value="success">Success</option>
                <option value="warning">Warning</option>
                <option value="danger">Danger</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('announcements.field_title') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('announcements.field_type') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('announcements.field_status') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('common.created_at') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="item in announcements.data"
                            :key="item.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="max-w-md px-4 py-3">
                                <div class="font-medium">{{ item.title }}</div>
                                <p
                                    class="line-clamp-1 text-xs text-muted-foreground"
                                >
                                    {{ item.content }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant="outline"
                                    class="uppercase"
                                    :class="typeBadgeClass(item.type)"
                                >
                                    {{ item.type }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        item.is_active ? 'default' : 'secondary'
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
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ item.created_at ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        v-if="can('announcements.edit')"
                                        variant="ghost"
                                        size="icon"
                                        :title="t('common.edit')"
                                        @click="openEditDialog(item)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="can('announcements.delete')"
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
                        <tr v-if="announcements.data.length === 0">
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
                v-if="announcements.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t('common.showing') }} {{ announcements.from }} -
                    {{ announcements.to }} {{ t('common.of') }}
                    {{ announcements.total }} {{ t('common.entries') }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template
                        v-for="link in announcements.links"
                        :key="link.label"
                    >
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
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingItem
                                ? t('announcements.edit')
                                : t('announcements.add')
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editingItem
                                ? t('announcements.edit_desc')
                                : t('announcements.create_desc')
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="announcement-title">
                            {{ t('announcements.field_title') }}
                        </Label>
                        <Input
                            id="announcement-title"
                            v-model="form.title"
                            type="text"
                            required
                        />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="announcement-content">
                            {{ t('announcements.field_content') }}
                        </Label>
                        <textarea
                            id="announcement-content"
                            v-model="form.content"
                            rows="4"
                            required
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.content" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="announcement-type">
                                {{ t('announcements.field_type') }}
                            </Label>
                            <select
                                id="announcement-type"
                                v-model="form.type"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="info">Info</option>
                                <option value="success">Success</option>
                                <option value="warning">Warning</option>
                                <option value="danger">Danger</option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="flex items-end pb-2">
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="size-4 rounded border-input accent-primary"
                                />
                                <span>
                                    {{ t('announcements.field_active') }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="announcement-published">
                                {{ t('announcements.field_published_at') }}
                            </Label>
                            <Input
                                id="announcement-published"
                                v-model="form.published_at"
                                type="datetime-local"
                            />
                            <InputError :message="form.errors.published_at" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="announcement-expires">
                                {{ t('announcements.field_expires_at') }}
                            </Label>
                            <Input
                                id="announcement-expires"
                                v-model="form.expires_at"
                                type="datetime-local"
                            />
                            <InputError :message="form.errors.expires_at" />
                        </div>
                    </div>

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
