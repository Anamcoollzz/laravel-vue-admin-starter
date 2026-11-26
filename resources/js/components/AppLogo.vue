<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const name = computed(() => String(page.props.name ?? 'Laravel'));
const logo = computed(
    () =>
        (page.props.appSettings as { logo?: string | null } | undefined)
            ?.logo ?? null,
);
</script>

<template>
    <img
        v-if="logo"
        :src="logo"
        :alt="name"
        class="size-8 shrink-0 rounded-md object-contain"
    />
    <div
        v-else
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            name
        }}</span>
    </div>
</template>
