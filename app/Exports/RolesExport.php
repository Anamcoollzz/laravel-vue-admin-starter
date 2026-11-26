<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Spatie\Permission\Models\Role;

/**
 * @implements WithMapping<Role>
 */
class RolesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return Collection<int, Role>
     */
    public function collection(): Collection
    {
        return Role::query()->with('permissions:id,name')->orderBy('name')->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['name', 'permissions'];
    }

    /**
     * @param  Role  $role
     * @return array<int, string>
     */
    public function map($role): array
    {
        return [
            $role->name,
            $role->permissions->pluck('name')->implode(', '),
        ];
    }
}
