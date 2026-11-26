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
import { useTranslation } from '@/composables/useTranslation';

type PermissionRow = {
    id: number;
    name: string;
    guard_name: string;
    roles_count: number;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedPermissions = {
    data: PermissionRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    permissions: PaginatedPermissions;
    filters: {
        search: string;
    };
}>();

const { t } = useTranslation();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Permission Management',
                href: '/app/permissions',
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
            '/app/permissions',
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const isDialogOpen = ref(false);
const editingPermission = ref<PermissionRow | null>(null);

const form = useForm({
    name: '',
});

const openCreateDialog = () => {
    editingPermission.value = null;
    form.reset();
    form.clearErrors();
    isDialogOpen.value = true;
};

const openEditDialog = (permission: PermissionRow) => {
    editingPermission.value = permission;
    form.reset();
    form.clearErrors();
    form.name = permission.name;
    isDialogOpen.value = true;
};

const submitForm = () => {
    if (editingPermission.value) {
        form.put(`/app/permissions/${editingPermission.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        form.post('/app/permissions', {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

const deletePermission = (permission: PermissionRow) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(`/app/permissions/${permission.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('permissions.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t('permissions.title') }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t('permissions.description') }}
                </p>
            </div>

            <Button @click="openCreateDialog">
                <Plus class="mr-2 size-4" />
                {{ t('permissions.add') }}
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
                    :placeholder="t('permissions.search_placeholder')"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('common.name') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('permissions.guard') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('permissions.roles_count') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="permission in permissions.data"
                            :key="permission.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ permission.name }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="outline">
                                    {{ permission.guard_name }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ permission.roles_count }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :title="t('common.edit')"
                                        @click="openEditDialog(permission)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :title="t('common.delete')"
                                        @click="deletePermission(permission)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="permissions.data.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t('common.no_data') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="permissions.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t('common.showing') }} {{ permissions.from }} -
                    {{ permissions.to }} {{ t('common.of') }}
                    {{ permissions.total }} {{ t('common.entries') }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template
                        v-for="link in permissions.links"
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
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingPermission
                                ? t('permissions.edit')
                                : t('permissions.add')
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editingPermission
                                ? t('permissions.edit_desc')
                                : t('permissions.create_desc')
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="permission-name">
                            {{ t('common.name') }}
                        </Label>
                        <Input
                            id="permission-name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. users.view"
                            required
                        />
                        <InputError :message="form.errors.name" />
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
