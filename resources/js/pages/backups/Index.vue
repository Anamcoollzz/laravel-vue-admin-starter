<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Database, Download, Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';

type BackupRow = {
    filename: string;
    size: string;
    created_at: string;
};

defineProps<{
    backups: BackupRow[];
    driver: string;
}>();

const { t } = useTranslation();
const { can } = usePermission();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Database Backup',
                href: '/app/backups',
            },
        ],
    },
});

const createForm = useForm({});

const createBackup = () => {
    createForm.post('/app/backups', {
        preserveScroll: true,
    });
};

const deleteBackup = (filename: string) => {
    if (!window.confirm(t('common.confirm_delete'))) {
        return;
    }

    router.delete(`/app/backups/${encodeURIComponent(filename)}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('backups.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ t('backups.title') }}
                    </h1>
                    <Badge variant="outline" class="uppercase">
                        <Database class="mr-1 size-3" />
                        {{ driver }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t('backups.description') }}
                </p>
            </div>

            <Button
                v-if="can('backups.create')"
                :disabled="createForm.processing"
                @click="createBackup"
            >
                <Plus class="mr-2 size-4" />
                {{
                    createForm.processing
                        ? t('backups.creating')
                        : t('backups.create')
                }}
            </Button>
        </div>

        <InputError :message="(createForm.errors as Record<string, string>).backup" />

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('backups.filename') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('backups.size') }}
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
                            v-for="backup in backups"
                            :key="backup.filename"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                {{ backup.filename }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ backup.size }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ backup.created_at }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        :title="t('backups.download')"
                                        as-child
                                    >
                                        <a
                                            :href="`/app/backups/${encodeURIComponent(backup.filename)}/download`"
                                        >
                                            <Download class="size-4" />
                                        </a>
                                    </Button>
                                    <Button
                                        v-if="can('backups.delete')"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        :title="t('common.delete')"
                                        @click="deleteBackup(backup.filename)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="backups.length === 0">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{ t('backups.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
