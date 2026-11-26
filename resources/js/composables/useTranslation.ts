import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type Locale = 'en' | 'id';

const messages: Record<Locale, Record<string, string>> = {
    en: {
        // Navigation
        'nav.platform': 'Platform',
        'nav.access_control': 'Access Control',
        'nav.dashboard': 'Dashboard',
        'nav.messages': 'Messages',
        'nav.announcements': 'Announcements',
        'nav.users': 'User Management',
        'nav.roles': 'Role Management',
        'nav.permissions': 'Permission Management',
        'nav.backups': 'Database Backup',
        'nav.database': 'Database Explorer',
        'nav.files': 'File Manager',
        'nav.logs': 'Request Logs',
        'nav.laravel_logs': 'Laravel Log Viewer',
        'nav.app_settings': 'App Settings',

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
        'common.export': 'Export Excel',
        'common.import': 'Import Excel',
        'common.importing': 'Importing...',
        'common.import_file': 'Excel / CSV File',
        'common.import_columns': 'Expected columns',
        'common.download_template': 'Download Template',
        'common.template_hint':
            'Not sure about the format? Use the sample template.',

        // Users
        'users.title': 'User Management',
        'users.description':
            'Manage application users and their assigned roles.',
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

        // User Menu & Settings Layout
        'menu.settings': 'Settings',
        'menu.logout': 'Log out',
        'settings.title': 'Settings',
        'settings.description': 'Manage your profile and account settings',
        'settings.profile': 'Profile',
        'settings.security': 'Security',
        'settings.appearance': 'Appearance',

        // Profile Settings
        'profile.page_title': 'Profile settings',
        'profile.title': 'Profile',
        'profile.description':
            'Update your avatar, name, email address, and social links',
        'profile.avatar': 'Avatar',
        'profile.upload_photo': 'Upload photo',
        'profile.remove': 'Remove',
        'profile.avatar_hint': 'JPG, PNG, or WEBP. Max 2 MB.',
        'profile.full_name': 'Full name',
        'profile.email_address': 'Email address',
        'profile.email_unverified': 'Your email address is unverified.',
        'profile.resend_verification':
            'Click here to re-send the verification email.',
        'profile.verification_sent':
            'A new verification link has been sent to your email address.',
        'profile.social_links': 'Social links',
        'profile.social_links_desc':
            'Add links to your social media profiles (optional).',

        // Appearance Settings
        'appearance.title': 'Appearance settings',
        'appearance.description':
            'Update the appearance settings for your account',
        'appearance.light': 'Light',
        'appearance.dark': 'Dark',
        'appearance.system': 'System',

        // Delete Account
        'delete_account.title': 'Delete account',
        'delete_account.description':
            'Delete your account and all of its resources',
        'delete_account.warning': 'Warning',
        'delete_account.warning_desc':
            'Please proceed with caution, this cannot be undone.',
        'delete_account.confirm_title':
            'Are you sure you want to delete your account?',
        'delete_account.confirm_desc':
            'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',

        // Database Backup
        'backups.title': 'Database Backup',
        'backups.description': 'Create, download, and manage database backups.',
        'backups.create': 'Create Backup',
        'backups.creating': 'Creating...',
        'backups.filename': 'Filename',
        'backups.size': 'Size',
        'backups.download': 'Download',
        'backups.driver': 'Driver',
        'backups.empty': 'No backups created yet.',

        // Database Explorer
        'database.title': 'Database Explorer',
        'database.description':
            'Browse database tables, inspect fields, and manage records.',
        'database.table_name': 'Table Name',
        'database.columns': 'Fields',
        'database.rows': 'Rows',
        'database.open': 'Open',
        'database.back': 'Back to tables',
        'database.search_tables': 'Search table name...',
        'database.tab_data': 'Data',
        'database.tab_fields': 'Fields',
        'database.field_name': 'Field Name',
        'database.field_type': 'Type',
        'database.nullable': 'Nullable',
        'database.default': 'Default',
        'database.rename_field': 'Edit Field',
        'database.rename_field_desc':
            'Update column name, data type, nullability, or index.',
        'database.new_field_name': 'Field Name',
        'database.index': 'Index / Unique',
        'database.index_none': 'None',
        'database.index_normal': 'Index',
        'database.index_unique': 'Unique',
        'database.edit_row': 'Edit Row',
        'database.edit_row_desc': 'Update column values for this record.',
        'database.no_pk_notice':
            'Editing and deleting rows requires a single-column primary key.',
        'database.yes': 'Yes',
        'database.no': 'No',

        // File Manager
        'files.title': 'File Manager',
        'files.description':
            'Browse, upload, and manage files in public storage.',
        'files.root': 'Root',
        'files.new_folder': 'New Folder',
        'files.new_folder_desc':
            'Create a new folder in the current directory.',
        'files.folder_name': 'Folder Name',
        'files.upload': 'Upload File',
        'files.upload_desc':
            'Upload a file to the current directory (max 10 MB).',
        'files.uploading': 'Uploading...',
        'files.choose_file': 'Choose File',
        'files.size': 'Size',
        'files.modified': 'Modified',
        'files.copy_link': 'Copy Link',
        'files.copied': 'Copied!',
        'files.empty': 'This folder is empty.',

        // Request Logs
        'logs.title': 'Request Logs',
        'logs.description':
            'Monitor incoming HTTP requests, users, roles, IP addresses, and payloads.',
        'logs.clear_all': 'Clear All',
        'logs.confirm_clear':
            'Are you sure you want to delete all request logs?',
        'logs.all_methods': 'All Methods',
        'logs.search_placeholder': 'Search path, IP, browser, or user...',
        'logs.user': 'User',
        'logs.guest': 'Guest',
        'logs.method': 'Method',
        'logs.path': 'Path',
        'logs.ip_browser': 'IP & Browser',
        'logs.time': 'Time',
        'logs.detail': 'Request Detail',
        'logs.detail_desc': 'Full metadata and sanitized request payload.',
        'logs.status': 'Status',
        'logs.duration': 'Duration',
        'logs.route': 'Route Name',
        'logs.platform': 'Platform',
        'logs.user_agent': 'User Agent',
        'logs.referer': 'Referer',
        'logs.payload': 'Request Payload',
        'logs.no_payload': 'No payload',

        // Laravel Log Viewer
        'laravel_logs.title': 'Laravel Log Viewer',
        'laravel_logs.description':
            'Inspect, filter, and manage entries inside storage/logs files.',
        'laravel_logs.all_levels': 'All Levels',
        'laravel_logs.search_placeholder': 'Search message or stack trace...',
        'laravel_logs.download': 'Download Log',
        'laravel_logs.clear': 'Clear Log',
        'laravel_logs.confirm_clear':
            'Are you sure you want to clear this log file?',
        'laravel_logs.level': 'Level',
        'laravel_logs.message': 'Message',
        'laravel_logs.env': 'Env',
        'laravel_logs.stack_trace': 'Stack Trace',
        'laravel_logs.view_detail': 'View Detail',
        'laravel_logs.no_files': 'No log files found in storage/logs.',
        'laravel_logs.no_entries': 'No log entries found.',

        // Announcements
        'announcements.title': 'Announcements',
        'announcements.description':
            'Create and manage announcements displayed on the dashboard.',
        'announcements.add': 'Add Announcement',
        'announcements.edit': 'Edit Announcement',
        'announcements.create_desc':
            'Create a new announcement to display on the dashboard.',
        'announcements.edit_desc':
            'Update announcement content and visibility.',
        'announcements.field_title': 'Title',
        'announcements.field_content': 'Content',
        'announcements.field_type': 'Type',
        'announcements.field_status': 'Status',
        'announcements.field_active': 'Active (show on dashboard)',
        'announcements.field_published_at': 'Publish At (optional)',
        'announcements.field_expires_at': 'Expires At (optional)',
        'announcements.active': 'Active',
        'announcements.inactive': 'Inactive',
        'announcements.all_types': 'All Types',
        'announcements.search_placeholder': 'Search title or content...',

        // Messages / Chat
        'messages.title': 'Messages',
        'messages.search_users': 'Search user...',
        'messages.no_contacts': 'No users found.',
        'messages.select_conversation': 'Select a user to start chatting.',
        'messages.no_messages': 'No messages yet. Say hello!',
        'messages.type_placeholder': 'Type a message...',
        'messages.send': 'Send',

        // Notifications
        'notifications.title': 'Notifications',
        'notifications.mark_all_read': 'Mark all as read',
        'notifications.empty': 'No notifications yet.',

        // App Settings
        'app_settings.title': 'Application Settings',
        'app_settings.description':
            'Manage general configuration, SMTP mail server, and privacy policy.',
        'app_settings.group_general': 'General Settings',
        'app_settings.group_smtp': 'SMTP Mail',
        'app_settings.group_privacy': 'Privacy Policy',
        'app_settings.app_name': 'Application Name',
        'app_settings.logo': 'Logo',
        'app_settings.favicon': 'Favicon',
        'app_settings.country': 'Country',
        'app_settings.city': 'City',
        'app_settings.year': 'Year',
        'app_settings.smtp_host': 'SMTP Host',
        'app_settings.smtp_port': 'SMTP Port',
        'app_settings.smtp_username': 'SMTP Username',
        'app_settings.smtp_password': 'SMTP Password',
        'app_settings.smtp_password_keep': 'Leave blank to keep saved password',
        'app_settings.smtp_encryption': 'Encryption',
        'app_settings.smtp_from_address': 'From Email Address',
        'app_settings.smtp_from_name': 'From Name',
        'app_settings.privacy_policy': 'Privacy Policy Content',
        'app_settings.privacy_placeholder':
            'Write your application privacy policy here...',
        'app_settings.group_security': 'Security',
        'app_settings.max_login_attempts': 'Max Login Attempts',
        'app_settings.max_login_attempts_desc':
            'Maximum failed login attempts before temporary lockout',
        'app_settings.lockout_minutes': 'Login Lockout Duration (minutes)',
        'app_settings.lockout_minutes_desc':
            'Lockout duration in minutes after exceeding login attempts',
        'app_settings.password_min_length': 'Minimum Password Length',
        'app_settings.password_mixed_case': 'Require Uppercase & Lowercase',
        'app_settings.password_mixed_case_desc':
            'Password must contain both uppercase and lowercase letters',
        'app_settings.password_numbers': 'Require Numbers',
        'app_settings.password_numbers_desc':
            'Password must contain at least one number',
        'app_settings.password_symbols': 'Require Symbols',
        'app_settings.password_symbols_desc':
            'Password must contain at least one special character',
        'app_settings.password_uncompromised': 'Check Compromised Passwords',
        'app_settings.password_uncompromised_desc':
            'Reject passwords that have appeared in data breaches',
        'app_settings.session_lifetime': 'Session Lifetime (minutes)',
    },
    id: {
        // Navigation
        'nav.platform': 'Platform',
        'nav.access_control': 'Kontrol Akses',
        'nav.dashboard': 'Dasbor',
        'nav.messages': 'Pesan',
        'nav.announcements': 'Pengumuman',
        'nav.users': 'Manajemen Pengguna',
        'nav.roles': 'Manajemen Role',
        'nav.permissions': 'Manajemen Izin',
        'nav.backups': 'Backup Database',
        'nav.database': 'Jelajah Database',
        'nav.files': 'Manajer File',
        'nav.logs': 'Log Request',
        'nav.laravel_logs': 'Laravel Log Viewer',
        'nav.app_settings': 'App Settings',

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
        'common.password_optional':
            'Kosongkan jika tidak ingin mengubah kata sandi',
        'common.created_at': 'Dibuat Pada',
        'common.previous': 'Sebelumnya',
        'common.next': 'Berikutnya',
        'common.showing': 'Menampilkan',
        'common.of': 'dari',
        'common.entries': 'data',
        'common.export': 'Ekspor Excel',
        'common.import': 'Impor Excel',
        'common.importing': 'Mengimpor...',
        'common.import_file': 'File Excel / CSV',
        'common.import_columns': 'Kolom yang didukung',
        'common.download_template': 'Unduh Template',
        'common.template_hint': 'Bingung formatnya? Gunakan contoh template.',

        // Users
        'users.title': 'Manajemen Pengguna',
        'users.description':
            'Kelola pengguna aplikasi beserta role yang diberikan.',
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
        'permissions.description':
            'Kelola izin akses aplikasi secara granular.',
        'permissions.add': 'Tambah Izin',
        'permissions.edit': 'Ubah Izin',
        'permissions.create_desc': 'Buat izin akses baru.',
        'permissions.edit_desc': 'Perbarui nama izin akses.',
        'permissions.roles_count': 'Role Terkait',
        'permissions.guard': 'Guard',
        'permissions.search_placeholder': 'Cari nama izin...',

        // User Menu & Settings Layout
        'menu.settings': 'Pengaturan',
        'menu.logout': 'Keluar',
        'settings.title': 'Pengaturan',
        'settings.description': 'Kelola profil dan pengaturan akun Anda',
        'settings.profile': 'Profil',
        'settings.security': 'Keamanan',
        'settings.appearance': 'Tampilan',

        // Profile Settings
        'profile.page_title': 'Pengaturan profil',
        'profile.title': 'Profil',
        'profile.description':
            'Perbarui avatar, nama, alamat email, dan tautan media sosial Anda',
        'profile.avatar': 'Avatar',
        'profile.upload_photo': 'Unggah foto',
        'profile.remove': 'Hapus',
        'profile.avatar_hint': 'JPG, PNG, atau WEBP. Maks 2 MB.',
        'profile.full_name': 'Nama lengkap',
        'profile.email_address': 'Alamat email',
        'profile.email_unverified': 'Alamat email Anda belum terverifikasi.',
        'profile.resend_verification':
            'Klik di sini untuk mengirim ulang email verifikasi.',
        'profile.verification_sent':
            'Tautan verifikasi baru telah dikirim ke alamat email Anda.',
        'profile.social_links': 'Tautan media sosial',
        'profile.social_links_desc':
            'Tambahkan tautan ke profil media sosial Anda (opsional).',

        // Appearance Settings
        'appearance.title': 'Pengaturan tampilan',
        'appearance.description': 'Perbarui pengaturan tampilan akun Anda',
        'appearance.light': 'Terang',
        'appearance.dark': 'Gelap',
        'appearance.system': 'Sistem',

        // Delete Account
        'delete_account.title': 'Hapus akun',
        'delete_account.description': 'Hapus akun Anda beserta seluruh datanya',
        'delete_account.warning': 'Peringatan',
        'delete_account.warning_desc':
            'Harap berhati-hati, tindakan ini tidak dapat dibatalkan.',
        'delete_account.confirm_title':
            'Apakah Anda yakin ingin menghapus akun Anda?',
        'delete_account.confirm_desc':
            'Setelah akun dihapus, seluruh data dan sumber daya terkait akan dihapus secara permanen. Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun secara permanen.',

        // Database Backup
        'backups.title': 'Backup Database',
        'backups.description': 'Buat, unduh, dan kelola file backup database.',
        'backups.create': 'Buat Backup',
        'backups.creating': 'Membuat...',
        'backups.filename': 'Nama File',
        'backups.size': 'Ukuran',
        'backups.download': 'Unduh',
        'backups.driver': 'Driver',
        'backups.empty': 'Belum ada file backup.',

        // Database Explorer
        'database.title': 'Jelajah Database',
        'database.description':
            'Lihat daftar tabel, struktur field, dan kelola data secara langsung.',
        'database.table_name': 'Nama Tabel',
        'database.columns': 'Field',
        'database.rows': 'Baris Data',
        'database.open': 'Buka',
        'database.back': 'Kembali ke daftar tabel',
        'database.search_tables': 'Cari nama tabel...',
        'database.tab_data': 'Data',
        'database.tab_fields': 'Struktur Field',
        'database.field_name': 'Nama Field',
        'database.field_type': 'Tipe',
        'database.nullable': 'Nullable',
        'database.default': 'Default',
        'database.rename_field': 'Ubah Field',
        'database.rename_field_desc':
            'Perbarui nama kolom, tipe data, nullable, atau index.',
        'database.new_field_name': 'Nama Field',
        'database.index': 'Index / Unique',
        'database.index_none': 'Tidak ada',
        'database.index_normal': 'Index',
        'database.index_unique': 'Unique',
        'database.edit_row': 'Ubah Data',
        'database.edit_row_desc': 'Perbarui nilai kolom untuk baris data ini.',
        'database.no_pk_notice':
            'Edit dan hapus data memerlukan primary key satu kolom.',
        'database.yes': 'Ya',
        'database.no': 'Tidak',

        // File Manager
        'files.title': 'Manajer File',
        'files.description':
            'Jelajahi, unggah, dan kelola file di penyimpanan publik.',
        'files.root': 'Root',
        'files.new_folder': 'Folder Baru',
        'files.new_folder_desc': 'Buat folder baru di direktori saat ini.',
        'files.folder_name': 'Nama Folder',
        'files.upload': 'Unggah File',
        'files.upload_desc': 'Unggah file ke direktori saat ini (maks 10 MB).',
        'files.uploading': 'Mengunggah...',
        'files.choose_file': 'Pilih File',
        'files.size': 'Ukuran',
        'files.modified': 'Diubah',
        'files.copy_link': 'Salin Tautan',
        'files.copied': 'Tersalin!',
        'files.empty': 'Folder ini masih kosong.',

        // Request Logs
        'logs.title': 'Log Request',
        'logs.description':
            'Pantau aktivitas request HTTP, pengguna, role, alamat IP, dan payload.',
        'logs.clear_all': 'Hapus Semua',
        'logs.confirm_clear':
            'Apakah Anda yakin ingin menghapus semua log request?',
        'logs.all_methods': 'Semua Method',
        'logs.search_placeholder': 'Cari path, IP, browser, atau user...',
        'logs.user': 'Pengguna',
        'logs.guest': 'Tamu',
        'logs.method': 'Method',
        'logs.path': 'Path',
        'logs.ip_browser': 'IP & Browser',
        'logs.time': 'Waktu',
        'logs.detail': 'Detail Request',
        'logs.detail_desc':
            'Metadata lengkap dan payload request yang disanitasi.',
        'logs.status': 'Status',
        'logs.duration': 'Durasi',
        'logs.route': 'Nama Route',
        'logs.platform': 'Platform',
        'logs.user_agent': 'User Agent',
        'logs.referer': 'Referer',
        'logs.payload': 'Payload Request',
        'logs.no_payload': 'Tidak ada payload',

        // Laravel Log Viewer
        'laravel_logs.title': 'Laravel Log Viewer',
        'laravel_logs.description':
            'Bedah, filter, dan kelola isi file log di storage/logs.',
        'laravel_logs.all_levels': 'Semua Level',
        'laravel_logs.search_placeholder':
            'Cari pesan error atau stack trace...',
        'laravel_logs.download': 'Unduh Log',
        'laravel_logs.clear': 'Kosongkan Log',
        'laravel_logs.confirm_clear':
            'Apakah Anda yakin ingin mengosongkan file log ini?',
        'laravel_logs.level': 'Level',
        'laravel_logs.message': 'Pesan',
        'laravel_logs.env': 'Env',
        'laravel_logs.stack_trace': 'Stack Trace',
        'laravel_logs.view_detail': 'Lihat Detail',
        'laravel_logs.no_files': 'Tidak ada file log di storage/logs.',
        'laravel_logs.no_entries': 'Tidak ada entri log ditemukan.',

        // Announcements
        'announcements.title': 'Pengumuman',
        'announcements.description':
            'Buat dan kelola pengumuman yang tampil di halaman dasbor.',
        'announcements.add': 'Tambah Pengumuman',
        'announcements.edit': 'Ubah Pengumuman',
        'announcements.create_desc':
            'Buat pengumuman baru untuk ditampilkan di dasbor.',
        'announcements.edit_desc': 'Perbarui isi dan status pengumuman.',
        'announcements.field_title': 'Judul',
        'announcements.field_content': 'Isi Pengumuman',
        'announcements.field_type': 'Tipe',
        'announcements.field_status': 'Status',
        'announcements.field_active': 'Aktif (tampilkan di dasbor)',
        'announcements.field_published_at': 'Mulai Tayang (opsional)',
        'announcements.field_expires_at': 'Berakhir Pada (opsional)',
        'announcements.active': 'Aktif',
        'announcements.inactive': 'Nonaktif',
        'announcements.all_types': 'Semua Tipe',
        'announcements.search_placeholder': 'Cari judul atau isi pengumuman...',

        // Messages / Chat
        'messages.title': 'Pesan',
        'messages.search_users': 'Cari pengguna...',
        'messages.no_contacts': 'Pengguna tidak ditemukan.',
        'messages.select_conversation': 'Pilih pengguna untuk mulai mengobrol.',
        'messages.no_messages': 'Belum ada pesan. Mulai percakapan sekarang!',
        'messages.type_placeholder': 'Tulis pesan...',
        'messages.send': 'Kirim',

        // Notifications
        'notifications.title': 'Notifikasi',
        'notifications.mark_all_read': 'Tandai semua dibaca',
        'notifications.empty': 'Belum ada notifikasi.',

        // App Settings
        'app_settings.title': 'Pengaturan Aplikasi',
        'app_settings.description':
            'Kelola pengaturan umum, server email SMTP, dan kebijakan privasi.',
        'app_settings.group_general': 'Pengaturan Umum',
        'app_settings.group_smtp': 'SMTP Email',
        'app_settings.group_privacy': 'Kebijakan Privasi',
        'app_settings.app_name': 'Nama Aplikasi',
        'app_settings.logo': 'Logo',
        'app_settings.favicon': 'Favicon',
        'app_settings.country': 'Negara',
        'app_settings.city': 'Kota',
        'app_settings.year': 'Tahun',
        'app_settings.smtp_host': 'Host SMTP',
        'app_settings.smtp_port': 'Port SMTP',
        'app_settings.smtp_username': 'Username SMTP',
        'app_settings.smtp_password': 'Password SMTP',
        'app_settings.smtp_password_keep':
            'Kosongkan jika tidak ingin mengubah password',
        'app_settings.smtp_encryption': 'Enkripsi',
        'app_settings.smtp_from_address': 'Alamat Email Pengirim',
        'app_settings.smtp_from_name': 'Nama Pengirim',
        'app_settings.privacy_policy': 'Isi Kebijakan Privasi',
        'app_settings.privacy_placeholder':
            'Tulis kebijakan privasi aplikasi Anda di sini...',
        'app_settings.group_security': 'Keamanan',
        'app_settings.max_login_attempts': 'Maksimal Percobaan Login',
        'app_settings.max_login_attempts_desc':
            'Maksimal percobaan login gagal sebelum dikunci sementara',
        'app_settings.lockout_minutes': 'Durasi Kunci Login (menit)',
        'app_settings.lockout_minutes_desc':
            'Durasi penguncian dalam menit setelah melebihi batas percobaan login',
        'app_settings.password_min_length': 'Panjang Minimal Kata Sandi',
        'app_settings.password_mixed_case': 'Wajib Huruf Besar & Kecil',
        'app_settings.password_mixed_case_desc':
            'Wajib mengandung huruf besar dan huruf kecil pada kata sandi',
        'app_settings.password_numbers': 'Wajib Angka',
        'app_settings.password_numbers_desc':
            'Wajib mengandung minimal satu angka pada kata sandi',
        'app_settings.password_symbols': 'Wajib Simbol',
        'app_settings.password_symbols_desc':
            'Wajib mengandung minimal satu karakter spesial pada kata sandi',
        'app_settings.password_uncompromised': 'Cek Kata Sandi Bocor',
        'app_settings.password_uncompromised_desc':
            'Tolak kata sandi yang pernah bocor di kebocoran data',
        'app_settings.session_lifetime': 'Masa Berlaku Sesi (menit)',
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
