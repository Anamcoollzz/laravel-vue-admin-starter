<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Database, Search, Table2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/composables/useTranslation';

type TableSummary = {
    name: string;
    columns_count: number;
    rows_count: number;
};

const props = defineProps<{
    tables: TableSummary[];
    driver: string;
}>();

const { t } = useTranslation();

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

const search = ref('');

const filteredTables = computed(() => {
    const query = search.value.trim().toLowerCase();

    if (!query) {
        return props.tables;
    }

    return props.tables.filter((table) =>
        table.name.toLowerCase().includes(query),
    );
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Head :title="t('database.title')" />

        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ t('database.title') }}
                    </h1>
                    <Badge variant="outline" class="uppercase">
                        <Database class="mr-1 size-3" />
                        {{ driver }}
                    </Badge>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ t('database.description') }}
                </p>
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
                    :placeholder="t('database.search_tables')"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-border bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.table_name') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.columns') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('database.rows') }}
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="table in filteredTables"
                            :key="table.name"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                <div class="flex items-center gap-2">
                                    <Table2
                                        class="size-4 text-muted-foreground"
                                    />
                                    {{ table.name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ table.columns_count }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ table.rows_count }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/app/database/${table.name}`">
                                        {{ t('database.open') }}
                                        <ArrowRight class="ml-1.5 size-3.5" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="filteredTables.length === 0">
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
        </div>
    </div>
</template>
