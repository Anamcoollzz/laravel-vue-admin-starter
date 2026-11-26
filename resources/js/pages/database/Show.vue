<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Columns3,
    Key,
    Pencil,
    Search,
    Table2,
    Trash2,
} from '@lucide/vue';
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

type ColumnInfo = {
    name: string;
    type_name: string;
    type: string;
    nullable: boolean;
    default: string | null;
    auto_increment: boolean;
    index_type: 'primary' | 'unique' | 'index' | null;
    index_name: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedRows = {
    data: Record<string, unknown>[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    table: string;
    primaryKey: string | null;
    columns: ColumnInfo[];
    rows: PaginatedRows;
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
                title: 'Database Explorer',
                href: '/app/database',
            },
        ],
    },
});

const activeTab = ref<'data' | 'fields'>('data');
const search = ref(props.filters.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            `/app/database/${props.table}`,
            { search: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const fieldTypeOptions = [
    'string',
    'text',
    'integer',
    'bigInteger',
    'float',
    'decimal',
    'boolean',
    'date',
    'datetime',
    'timestamp',
    'json',
] as const;

const inferFieldType = (rawType: string): string => {
    const t = rawType.toLowerCase();

    if (t.includes('bigint')) return 'bigInteger';
    if (t.includes('int')) return 'integer';
    if (t.includes('bool')) return 'boolean';
    if (t.includes('decimal') || t.includes('numeric')) return 'decimal';
    if (t.includes('float') || t.includes('double') || t.includes('real')) return 'float';
    if (t.includes('datetime')) return 'datetime';
    if (t.includes('timestamp')) return 'timestamp';
    if (t === 'date') return 'date';
    if (t.includes('json')) return 'json';
    if (t.includes('text') || t.includes('clob')) return 'text';

    return 'string';
};

// Edit column dialog
const isRenameOpen = ref(false);
const renamingColumn = ref<ColumnInfo | null>(null);
const renameForm = useForm({
    new_name: '',
    type: 'string',
    nullable: false,
    index: 'none' as 'none' | 'index' | 'unique',
});

const openRenameDialog = (col: ColumnInfo) => {
    renamingColumn.value = col;
    renameForm.reset();
    renameForm.clearErrors();
    renameForm.new_name = col.name;
    renameForm.type = inferFieldType(col.type || col.type_name);
    renameForm.nullable = col.nullable;
    renameForm.index =
        col.index_type === 'unique' || col.index_type === 'index'
            ? col.index_type
            : 'none';
    isRenameOpen.value = true;
};

const submitRename = () => {
    if (!renamingColumn.value) {
        return;
    }

    renameForm.patch(
        `/app/database/${props.table}/columns/${encodeURIComponent(renamingColumn.value.name)}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                isRenameOpen.value = false;
            },
        },
    );
};

const dropColumn = (columnName: string) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(
        `/app/database/${props.table}/columns/${encodeURIComponent(columnName)}`,
        { preserveScroll: true },
    );
};

// Edit row dialog
const isEditRowOpen = ref(false);
const editingRowId = ref<string | null>(null);
const rowForm = useForm<{ values: Record<string, string> }>({
    values: {},
});

const openEditRowDialog = (row: Record<string, unknown>) => {
    if (!props.primaryKey) {
        return;
    }

    editingRowId.value = String(row[props.primaryKey] ?? '');
    rowForm.clearErrors();

    const editable: Record<string, string> = {};

    for (const col of props.columns) {
        if (col.name !== props.primaryKey) {
            const val = row[col.name];
            editable[col.name] = val === null || val === undefined ? '' : String(val);
        }
    }

    rowForm.values = editable;
    isEditRowOpen.value = true;
};

const submitRowUpdate = () => {
    if (!editingRowId.value) {
        return;
    }

    rowForm.put(
        `/app/database/${props.table}/rows/${encodeURIComponent(editingRowId.value)}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditRowOpen.value = false;
            },
        },
    );
};

const deleteRow = (row: Record<string, unknown>) => {
    if (!props.primaryKey) {
        return;
    }

    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    const rowId = String(row[props.primaryKey] ?? '');

    router.delete(
        `/app/database/${props.table}/rows/${encodeURIComponent(rowId)}`,
        { preserveScroll: true },
    );
};

const formatCell = (value: unknown): string => {
    if (value === null || value === undefined) {
        return 'NULL';
    }

    const str = String(value);

    return str.length > 60 ? `${str.slice(0, 60)}…` : str;
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="`${table} - ${t('database.title')}`" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-center gap-3">
                <Button variant="outline" size="icon" as-child>
                    <Link href="/app/database" :title="t('database.back')">
                        <ArrowLeft class="size-4" />
                    </Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-mono text-2xl font-semibold">
                            {{ table }}
                        </h1>
                        <Badge variant="secondary">
                            {{ columns.length }} {{ t('database.columns') }}
                        </Badge>
                        <Badge variant="outline">
                            {{ rows.total }} {{ t('database.rows') }}
                        </Badge>
                    </div>
                </div>
            </div>

            <div
                class="inline-flex rounded-lg border border-border bg-muted/40 p-1"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        activeTab === 'data'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'data'"
                >
                    <Table2 class="size-4" />
                    {{ t('database.tab_data') }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        activeTab === 'fields'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'fields'"
                >
                    <Columns3 class="size-4" />
                    {{ t('database.tab_fields') }}
                </button>
            </div>
        </div>

        <!-- DATA TAB -->
        <div v-if="activeTab === 'data'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="relative w-full max-w-sm">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        class="pl-9"
                        :placeholder="t('common.search')"
                    />
                </div>
                <p
                    v-if="!primaryKey"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('database.no_pk_notice') }}
                </p>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-border bg-card"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border bg-muted/40">
                            <tr>
                                <th
                                    v-for="col in columns"
                                    :key="col.name"
                                    class="whitespace-nowrap px-4 py-3 font-mono text-xs font-medium"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <Key
                                            v-if="col.name === primaryKey"
                                            class="size-3 text-amber-500"
                                        />
                                        {{ col.name }}
                                    </span>
                                </th>
                                <th
                                    v-if="
                                        primaryKey &&
                                        (can('database.edit') ||
                                            can('database.delete'))
                                    "
                                    class="whitespace-nowrap px-4 py-3 text-right font-medium"
                                >
                                    {{ t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(row, idx) in rows.data"
                                :key="
                                    primaryKey
                                        ? String(row[primaryKey])
                                        : idx
                                "
                                class="hover:bg-muted/30"
                            >
                                <td
                                    v-for="col in columns"
                                    :key="col.name"
                                    class="max-w-xs truncate px-4 py-2.5 font-mono text-xs"
                                    :class="
                                        row[col.name] === null
                                            ? 'italic text-muted-foreground/60'
                                            : ''
                                    "
                                >
                                    {{ formatCell(row[col.name]) }}
                                </td>
                                <td
                                    v-if="
                                        primaryKey &&
                                        (can('database.edit') ||
                                            can('database.delete'))
                                    "
                                    class="whitespace-nowrap px-4 py-2.5 text-right"
                                >
                                    <div class="inline-flex items-center gap-1">
                                        <Button
                                            v-if="can('database.edit')"
                                            variant="ghost"
                                            size="icon"
                                            :title="t('common.edit')"
                                            @click="openEditRowDialog(row)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                        <Button
                                            v-if="can('database.delete')"
                                            variant="ghost"
                                            size="icon"
                                            class="text-destructive hover:text-destructive"
                                            :title="t('common.delete')"
                                            @click="deleteRow(row)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="rows.data.length === 0">
                                <td
                                    :colspan="columns.length + 1"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    {{ t('common.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="rows.total > 0"
                    class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-3 text-sm sm:flex-row"
                >
                    <p class="text-muted-foreground">
                        {{ t('common.showing') }} {{ rows.from }} -
                        {{ rows.to }} {{ t('common.of') }} {{ rows.total }}
                        {{ t('common.entries') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in rows.links" :key="link.label">
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

        <!-- FIELDS TAB -->
        <div
            v-else
            class="overflow-hidden rounded-xl border border-border bg-card"
        >
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.field_name') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.field_type') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.nullable') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.default') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.index') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="col in columns"
                            :key="col.name"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                <span class="inline-flex items-center gap-1.5">
                                    <Key
                                        v-if="col.name === primaryKey"
                                        class="size-3.5 text-amber-500"
                                    />
                                    {{ col.name }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="outline" class="font-mono">
                                    {{ col.type || col.type_name }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{
                                    col.nullable
                                        ? t('database.yes')
                                        : t('database.no')
                                }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                {{ col.default ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    v-if="col.index_type === 'primary'"
                                    variant="default"
                                    class="uppercase"
                                >
                                    PRIMARY
                                </Badge>
                                <Badge
                                    v-else-if="col.index_type === 'unique'"
                                    variant="secondary"
                                    class="uppercase"
                                >
                                    UNIQUE
                                </Badge>
                                <Badge
                                    v-else-if="col.index_type === 'index'"
                                    variant="outline"
                                    class="uppercase"
                                >
                                    INDEX
                                </Badge>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    -
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        v-if="can('database.edit')"
                                        variant="ghost"
                                        size="icon"
                                        :title="t('database.rename_field')"
                                        @click="openRenameDialog(col)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        v-if="
                                            can('database.delete') &&
                                            col.name !== primaryKey
                                        "
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :title="t('common.delete')"
                                        @click="dropColumn(col.name)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rename Field Dialog -->
        <Dialog v-model:open="isRenameOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('database.rename_field') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('database.rename_field_desc') }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitRename">
                    <div class="grid gap-2">
                        <Label for="new-field-name">
                            {{ t('database.new_field_name') }}
                        </Label>
                        <Input
                            id="new-field-name"
                            v-model="renameForm.new_name"
                            type="text"
                            class="font-mono"
                            required
                        />
                        <InputError :message="renameForm.errors.new_name" />
                    </div>

                    <template
                        v-if="
                            renamingColumn &&
                            renamingColumn.name !== primaryKey
                        "
                    >
                        <div class="grid gap-2">
                            <Label for="field-type">
                                {{ t('database.field_type') }}
                            </Label>
                            <select
                                id="field-type"
                                v-model="renameForm.type"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 font-mono text-sm shadow-xs"
                            >
                                <option
                                    v-for="opt in fieldTypeOptions"
                                    :key="opt"
                                    :value="opt"
                                >
                                    {{ opt }}
                                </option>
                            </select>
                            <InputError :message="renameForm.errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="field-index">
                                {{ t('database.index') }}
                            </Label>
                            <select
                                id="field-index"
                                v-model="renameForm.index"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs"
                            >
                                <option value="none">
                                    {{ t('database.index_none') }}
                                </option>
                                <option value="index">
                                    {{ t('database.index_normal') }}
                                </option>
                                <option value="unique">
                                    {{ t('database.index_unique') }}
                                </option>
                            </select>
                            <InputError :message="renameForm.errors.index" />
                        </div>

                        <label
                            class="flex cursor-pointer items-center gap-2 text-sm"
                        >
                            <input
                                v-model="renameForm.nullable"
                                type="checkbox"
                                class="size-4 rounded border-input accent-primary"
                            />
                            <span>{{ t('database.nullable') }}</span>
                        </label>
                    </template>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isRenameOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="renameForm.processing">
                            {{
                                renameForm.processing
                                    ? t('common.saving')
                                    : t('common.save')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Row Dialog -->
        <Dialog v-model:open="isEditRowOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ t('database.edit_row') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('database.edit_row_desc') }}
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitRowUpdate">
                    <div
                        v-for="col in columns.filter(
                            (c) => c.name !== primaryKey,
                        )"
                        :key="col.name"
                        class="grid gap-1.5"
                    >
                        <Label
                            :for="`row-field-${col.name}`"
                            class="font-mono text-xs"
                        >
                            {{ col.name }}
                            <span class="text-muted-foreground">
                                ({{ col.type || col.type_name }})
                            </span>
                        </Label>
                        <Input
                            :id="`row-field-${col.name}`"
                            v-model="rowForm.values[col.name]"
                            type="text"
                        />
                    </div>

                    <InputError
                        :message="(rowForm.errors as Record<string, string>).row"
                    />

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="isEditRowOpen = false"
                        >
                            {{ t('common.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="rowForm.processing">
                            {{
                                rowForm.processing
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
