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

type UserRow = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: UserRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    users: PaginatedUsers;
    roles: string[];
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
                title: "User Management",
                href: "/app/users",
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
            "/app/users",
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const isDialogOpen = ref(false);
const editingUser = ref<UserRow | null>(null);

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    roles: [] as string[],
});

const openCreateDialog = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    form.roles = [];
    isDialogOpen.value = true;
};

const openEditDialog = (user: UserRow) => {
    editingUser.value = user;
    form.reset();
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = "";
    form.password_confirmation = "";
    form.roles = [...user.roles];
    isDialogOpen.value = true;
};

const toggleRole = (roleName: string) => {
    if (form.roles.includes(roleName)) {
        form.roles = form.roles.filter((r) => r !== roleName);
    } else {
        form.roles = [...form.roles, roleName];
    }
};

const submitForm = () => {
    if (editingUser.value) {
        form.put(`/app/users/${editingUser.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        form.post("/app/users", {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

const deleteUser = (user: UserRow) => {
    if (!window.confirm(t("common.confirm_delete"))) {
        return;
    }

    router.delete(`/app/users/${user.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('users.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ t("users.title") }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ t("users.description") }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <ExcelActions
                    v-if="can('users.create')"
                    export-url="/app/users/export"
                    import-url="/app/users/import"
                    columns-hint="name, email, password, roles"
                />
                <Button v-if="can('users.create')" @click="openCreateDialog">
                    <Plus class="mr-2 size-4" />
                    {{ t("users.add") }}
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
                    :placeholder="t('users.search_placeholder')"
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
                                {{ t("common.email") }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t("users.roles") }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t("common.created_at") }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t("common.actions") }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.email }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="role in user.roles"
                                        :key="role"
                                        variant="secondary"
                                    >
                                        {{ role }}
                                    </Badge>
                                    <span
                                        v-if="user.roles.length === 0"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t("users.no_roles") }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.created_at ?? "-" }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        v-if="can('users.edit')"
                                        :title="t('common.edit')"
                                        @click="openEditDialog(user)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        v-if="can('users.delete')"
                                        :title="t('common.delete')"
                                        @click="deleteUser(user)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t("common.no_data") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="users.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
            >
                <p class="text-muted-foreground">
                    {{ t("common.showing") }} {{ users.from }} - {{ users.to }}
                    {{ t("common.of") }} {{ users.total }}
                    {{ t("common.entries") }}
                </p>
                <div class="flex flex-wrap items-center gap-1">
                    <template v-for="link in users.links" :key="link.label">
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
                        {{ editingUser ? t("users.edit") : t("users.add") }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            editingUser
                                ? t("users.edit_desc")
                                : t("users.create_desc")
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitForm">
                    <div class="grid gap-2">
                        <Label for="user-name">{{ t("common.name") }}</Label>
                        <Input
                            id="user-name"
                            v-model="form.name"
                            type="text"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-email">{{ t("common.email") }}</Label>
                        <Input
                            id="user-email"
                            v-model="form.email"
                            type="email"
                            required
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-password">
                            {{ t("common.password") }}
                        </Label>
                        <Input
                            id="user-password"
                            v-model="form.password"
                            type="password"
                            :required="!editingUser"
                            :placeholder="
                                editingUser ? t('common.password_optional') : ''
                            "
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="user-password-confirmation">
                            {{ t("common.password_confirmation") }}
                        </Label>
                        <Input
                            id="user-password-confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            :required="!editingUser"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label>{{ t("users.roles") }}</Label>
                        <div
                            class="grid grid-cols-2 gap-2 rounded-lg border border-border p-3"
                        >
                            <label
                                v-for="role in roles"
                                :key="role"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="size-4 rounded border-input accent-primary"
                                    :checked="form.roles.includes(role)"
                                    @change="toggleRole(role)"
                                />
                                <span>{{ role }}</span>
                            </label>
                            <p
                                v-if="roles.length === 0"
                                class="col-span-2 text-xs text-muted-foreground"
                            >
                                {{ t("users.no_roles") }}
                            </p>
                        </div>
                        <InputError :message="form.errors.roles" />
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
