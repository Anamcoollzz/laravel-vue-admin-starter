<script setup lang="ts">
import { Languages } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useTranslation } from '@/composables/useTranslation';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { locale, setLocale } = useTranslation();
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-2">
            <div
                class="inline-flex items-center rounded-md border border-border bg-muted/40 p-0.5 text-xs font-medium"
                role="group"
                aria-label="Language switcher"
            >
                <Languages class="mx-1.5 size-3.5 text-muted-foreground" />
                <button
                    type="button"
                    class="rounded px-2 py-1 transition-colors"
                    :class="
                        locale === 'id'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="setLocale('id')"
                >
                    ID
                </button>
                <button
                    type="button"
                    class="rounded px-2 py-1 transition-colors"
                    :class="
                        locale === 'en'
                            ? 'bg-background text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="setLocale('en')"
                >
                    EN
                </button>
            </div>
        </div>
    </header>
</template>
