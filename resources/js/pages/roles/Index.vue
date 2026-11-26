<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { Pencil, Plus, Search, Trash2 } from "@lucide/vue";
import { ref, watch } from "vue";
import InputError from "@/components/InputError.vue";
import ExcelActions from "@/components/ExcelActions.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { usePermission } from "@/composables/usePermission";
import { useTranslation } from "@/composables/useTranslation";

type RoleRow = {
    id: number;
    name: string;
    permissions: string[];
    users_count: number;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedRoles = {
    data: RoleRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    roles: PaginatedRoles;
    permissions: string[];
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
                title: "Role Management",
                href: "/app/roles",
            },
        ],
    },
});

const search = ref(props.filters.search ?? "");
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            "/app/roles",
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const isDialogOpen = ref(false);
const editingRole = ref<RoleRow | null>(null);

const form = useForm({
    name: "",
    permissions: [] as string[],
});

const openCreateDialog = () => {
    editingRole.value = null;
    form.reset();
    form.clearErrors();
    form.permissions = [];
    isDialogOpen.value = true;
};

const openEditDialog = (role: RoleRow) => {
    editingRole.value = role;
    form.reset();
    form.clearErrors();
    form.name = role.name;
    form.permissions = [...role.permissions];
    isDialogOpen.value = true;
};

const togglePermission = (permissionName: string) => {
    if (form.permissions.includes(permissionName)) {
        form.permissions = form.permissions.filter((p) => p !== permissionName);
    } else {
        form.permissions = [...form.permissions, permissionName];
    }
};

const submitForm = () => {
    if (editingRole.value) {
        form.put(`/app/roles/${editingRole.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        form.post("/app/roles", {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

const deleteRole = (role: RoleRow) => {
    if (!window.confirm(t("common.confirm_delete"))) {
        return;
    }

    router.delete(`/app/roles/${role.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('roles.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t("roles.title") }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t("roles.description") }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <ExcelActions
                    v-if="can('roles.create')"
                    export-url="/app/roles/export"
                    import-url="/app/roles/import"
                    columns-hint="name, permissions"
                />
                <Button v-if="can('roles.create')" @click="openCreateDialog">
                    <Plus class="mr-2 size-4" />
                    {{ t("roles.add") }}
                </Button>
            </div>
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
                    :placeholder="t('roles.search_placeholder')"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t("common.name") }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t("roles.permissions") }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t("roles.users_count") }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t("common.actions") }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="role in roles.data"
                            :key="role.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ role.name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="permission in role.permissions"
                                        :key="permission"
                                        variant="secondary"
                                    >
                                        {{ permission }}
                                    </Badge>
                                    <span
                                        v-if="role.permissions.length === 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t("roles.no_permissions") }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ role.users_count }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        v-if="can('roles.edit')"
                                        :title="t('common.edit')"
                                        @click="openEditDialog(role)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        v-if="can('roles.delete')"
                                        :title="t('common.delete')"
                                        @click="deleteRole(role)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="roles.data.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t("common.no_data") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="roles.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t("common.showing") }} {{ roles.from }} - {{ roles.to }}
                    {{ t("common.of") }} {{ roles.total }}
                    {{ t("common.entries") }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="link in roles.links" :key="link.label">
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
                        {{ editingRole ? t("roles.edit") : t("roles.add") }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editingRole
                                ? t("roles.edit_desc")
                                : t("roles.create_desc")
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="role-name">{{ t("common.name") }}</Label>
                        <Input
                            id="role-name"
                            v-model="form.name"
                            type="text"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>{{ t("roles.permissions") }}</Label>
                        <div
                            class="grid max-h-60 grid-cols-2 gap-2 overflow-y-auto rounded-lg border border-border p-3"
                        >
                            <label
                                v-for="permission in permissions"
                                :key="permission"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-input accent-primary"
                                    :checked="
                                        form.permissions.includes(permission)
                                    "
                                    @change="togglePermission(permission)"
                                />
                                <span>{{ permission }}</span>
                            </label>
                            <p
                                v-if="permissions.length === 0"
                                class="col-span-2 text-xs text-muted-foreground"
                            >
                                {{ t("roles.no_permissions") }}
                            </p>
                        </div>
                        <InputError :message="form.errors.permissions" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isDialogOpen = false"
                        >
                            {{ t("common.cancel") }}
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            {{
                                form.processing
                                    ? t("common.saving")
                                    : t("common.save")
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
