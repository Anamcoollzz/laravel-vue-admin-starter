import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type Locale = 'en' | 'id';

const messages: Record<Locale, Record<string, string>> = {
    en: {
        // Navigation
        'nav.platform': 'Platform',
        'nav.access_control': 'Access Control',
        'nav.dashboard': 'Dashboard',
        'nav.users': 'User Management',
        'nav.roles': 'Role Management',
        'nav.permissions': 'Permission Management',

        // Common
        'common.search': 'Search...',
        'common.actions': 'Actions',
        'common.create': 'Create',
        'common.add': 'Add',
        'common.edit': 'Edit',
        'common.delete': 'Delete',
        'common.save': 'Save',
        'common.saving': 'Saving...',
        'common.cancel': 'Cancel',
        'common.confirm_delete': 'Are you sure you want to delete this item?',
        'common.no_data': 'No data found.',
        'common.name': 'Name',
        'common.email': 'Email',
        'common.password': 'Password',
        'common.password_confirmation': 'Confirm Password',
        'common.password_optional': 'Leave blank to keep current password',
        'common.created_at': 'Created At',
        'common.previous': 'Previous',
        'common.next': 'Next',
        'common.showing': 'Showing',
        'common.of': 'of',
        'common.entries': 'entries',

        // Users
        'users.title': 'User Management',
        'users.description': 'Manage application users and their assigned roles.',
        'users.add': 'Add User',
        'users.edit': 'Edit User',
        'users.create_desc': 'Create a new user account and assign roles.',
        'users.edit_desc': 'Update user information and assigned roles.',
        'users.roles': 'Roles',
        'users.no_roles': 'No roles',
        'users.search_placeholder': 'Search by name or email...',

        // Roles
        'roles.title': 'Role Management',
        'roles.description': 'Manage roles and their associated permissions.',
        'roles.add': 'Add Role',
        'roles.edit': 'Edit Role',
        'roles.create_desc': 'Create a new role and assign permissions.',
        'roles.edit_desc': 'Update role name and assigned permissions.',
        'roles.permissions': 'Permissions',
        'roles.users_count': 'Users',
        'roles.no_permissions': 'No permissions',
        'roles.search_placeholder': 'Search role name...',

        // Permissions
        'permissions.title': 'Permission Management',
        'permissions.description': 'Manage granular access permissions.',
        'permissions.add': 'Add Permission',
        'permissions.edit': 'Edit Permission',
        'permissions.create_desc': 'Create a new permission.',
        'permissions.edit_desc': 'Update permission name.',
        'permissions.roles_count': 'Assigned Roles',
        'permissions.guard': 'Guard',
        'permissions.search_placeholder': 'Search permission name...',
    },
    id: {
        // Navigation
        'nav.platform': 'Platform',
        'nav.access_control': 'Kontrol Akses',
        'nav.dashboard': 'Dasbor',
        'nav.users': 'Manajemen Pengguna',
        'nav.roles': 'Manajemen Role',
        'nav.permissions': 'Manajemen Izin',

        // Common
        'common.search': 'Cari...',
        'common.actions': 'Aksi',
        'common.create': 'Buat',
        'common.add': 'Tambah',
        'common.edit': 'Ubah',
        'common.delete': 'Hapus',
        'common.save': 'Simpan',
        'common.saving': 'Menyimpan...',
        'common.cancel': 'Batal',
        'common.confirm_delete': 'Apakah Anda yakin ingin menghapus data ini?',
        'common.no_data': 'Data tidak ditemukan.',
        'common.name': 'Nama',
        'common.email': 'Email',
        'common.password': 'Kata Sandi',
        'common.password_confirmation': 'Konfirmasi Kata Sandi',
        'common.password_optional': 'Kosongkan jika tidak ingin mengubah kata sandi',
        'common.created_at': 'Dibuat Pada',
        'common.previous': 'Sebelumnya',
        'common.next': 'Berikutnya',
        'common.showing': 'Menampilkan',
        'common.of': 'dari',
        'common.entries': 'data',

        // Users
        'users.title': 'Manajemen Pengguna',
        'users.description': 'Kelola pengguna aplikasi beserta role yang diberikan.',
        'users.add': 'Tambah Pengguna',
        'users.edit': 'Ubah Pengguna',
        'users.create_desc': 'Buat akun pengguna baru dan tetapkan role.',
        'users.edit_desc': 'Perbarui informasi pengguna dan role.',
        'users.roles': 'Role',
        'users.no_roles': 'Tanpa role',
        'users.search_placeholder': 'Cari berdasarkan nama atau email...',

        // Roles
        'roles.title': 'Manajemen Role',
        'roles.description': 'Kelola role beserta daftar izinnya.',
        'roles.add': 'Tambah Role',
        'roles.edit': 'Ubah Role',
        'roles.create_desc': 'Buat role baru dan pilih izin yang diberikan.',
        'roles.edit_desc': 'Perbarui nama role dan daftar izin.',
        'roles.permissions': 'Izin',
        'roles.users_count': 'Pengguna',
        'roles.no_permissions': 'Tanpa izin',
        'roles.search_placeholder': 'Cari nama role...',

        // Permissions
        'permissions.title': 'Manajemen Izin',
        'permissions.description': 'Kelola izin akses aplikasi secara granular.',
        'permissions.add': 'Tambah Izin',
        'permissions.edit': 'Ubah Izin',
        'permissions.create_desc': 'Buat izin akses baru.',
        'permissions.edit_desc': 'Perbarui nama izin akses.',
        'permissions.roles_count': 'Role Terkait',
        'permissions.guard': 'Guard',
        'permissions.search_placeholder': 'Cari nama izin...',
    },
};

export function useTranslation() {
    const page = usePage();

    const locale = computed<Locale>(() => {
        const current = page.props.locale as string | undefined;

        return current === 'id' ? 'id' : 'en';
    });

    const t = (key: string): string => {
        return messages[locale.value]?.[key] ?? messages.en[key] ?? key;
    };

    const setLocale = (newLocale: Locale): void => {
        if (locale.value === newLocale) {
            return;
        }

        router.post(
            '/locale',
            { locale: newLocale },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return {
        locale,
        t,
        setLocale,
    };
}
