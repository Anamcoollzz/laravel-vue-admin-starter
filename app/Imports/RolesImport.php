<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesImport implements ToCollection, WithHeadingRow
{
    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $role = Role::findOrCreate($name, 'web');

            $permissionsRaw = trim((string) ($row['permissions'] ?? ''));

            if ($permissionsRaw !== '') {
                $permissionNames = array_filter(array_map('trim', explode(',', $permissionsRaw)));

                foreach ($permissionNames as $permissionName) {
                    Permission::findOrCreate($permissionName, 'web');
                }

                $role->syncPermissions($permissionNames);
            }
        }
    }
}
