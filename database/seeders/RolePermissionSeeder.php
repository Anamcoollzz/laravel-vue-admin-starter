<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'backups.view',
            'backups.create',
            'backups.delete',
            'database.view',
            'database.edit',
            'database.delete',
            'files.view',
            'files.create',
            'files.delete',
            'logs.view',
            'logs.delete',
            'announcements.view',
            'announcements.create',
            'announcements.edit',
            'announcements.delete',
            'settings.view',
            'settings.edit',
            'sessions.view',
            'sessions.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($permissions);

        $editorRole = Role::findOrCreate('editor', 'web');
        $editorRole->syncPermissions([
            'users.view',
            'roles.view',
            'permissions.view',
        ]);

        $viewerRole = Role::findOrCreate('viewer', 'web');
        $viewerRole->syncPermissions([
            'users.view',
        ]);

        $testUser = User::query()->where('email', 'test@example.com')->first();

        if ($testUser) {
            $testUser->assignRole($adminRole);
        }
    }
}
