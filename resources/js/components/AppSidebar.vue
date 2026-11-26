<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    BookOpen,
    Database,
    FileText,
    FolderGit2,
    FolderOpen,
    KeyRound,
    LayoutGrid,
    Megaphone,
    MessageSquare,
    ShieldCheck,
    SlidersHorizontal,
    Table2,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermission } from '@/composables/usePermission';
import { useTranslation } from '@/composables/useTranslation';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { t } = useTranslation();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('nav.messages'),
        href: '/app/messages',
        icon: MessageSquare,
    },
]);

const { can } = usePermission();

const accessControlNavItems = computed<NavItem[]>(() =>
    [
        {
            title: t('nav.announcements'),
            href: '/app/announcements',
            icon: Megaphone,
            permission: 'announcements.view',
        },
        {
            title: t('nav.users'),
            href: '/app/users',
            icon: Users,
            permission: 'users.view',
        },
        {
            title: t('nav.roles'),
            href: '/app/roles',
            icon: ShieldCheck,
            permission: 'roles.view',
        },
        {
            title: t('nav.permissions'),
            href: '/app/permissions',
            icon: KeyRound,
            permission: 'permissions.view',
        },
        {
            title: t('nav.backups'),
            href: '/app/backups',
            icon: Database,
            permission: 'backups.view',
        },
        {
            title: t('nav.database'),
            href: '/app/database',
            icon: Table2,
            permission: 'database.view',
        },
        {
            title: t('nav.files'),
            href: '/app/files',
            icon: FolderOpen,
            permission: 'files.view',
        },
        {
            title: t('nav.logs'),
            href: '/app/request-logs',
            icon: Activity,
            permission: 'logs.view',
        },
        {
            title: t('nav.laravel_logs'),
            href: '/app/laravel-logs',
            icon: FileText,
            permission: 'logs.view',
        },
        {
            title: t('nav.app_settings'),
            href: '/app/app-settings',
            icon: SlidersHorizontal,
            permission: 'settings.view',
        },
    ]
        .filter((item) => can(item.permission))
        .map(({ permission: _permission, ...item }) => item),
);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :label="t('nav.platform')" />
            <NavMain
                v-if="accessControlNavItems.length > 0"
                :items="accessControlNavItems"
                :label="t('nav.access_control')"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
