<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Permission\Models\Permission;

/**
 * @implements WithMapping<Permission>
 */
class PermissionsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return Collection<int, Permission>
     */
    public function collection(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['name', 'guard_name'];
    }

    /**
     * @param  Permission  $permission
     * @return array<int, string>
     */
    public function map($permission): array
    {
        return [
            $permission->name,
            $permission->guard_name,
        ];
    }
}
