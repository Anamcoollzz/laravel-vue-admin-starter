import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { AppPageProps } from '@/types';

export function usePermission() {
    const page = usePage<AppPageProps>();

    const roles = computed(() => page.props.auth?.roles ?? []);
    const permissions = computed(() => page.props.auth?.permissions ?? []);

    const hasRole = (role: string): boolean => roles.value.includes(role);

    const hasAnyRole = (roleList: string[]): boolean =>
        roleList.some((role) => roles.value.includes(role));

    const can = (permission: string): boolean =>
        permissions.value.includes(permission);

    const canAny = (permissionList: string[]): boolean =>
        permissionList.some((permission) =>
            permissions.value.includes(permission),
        );

    return {
        roles,
        permissions,
        hasRole,
        hasAnyRole,
        can,
        canAny,
    };
}
