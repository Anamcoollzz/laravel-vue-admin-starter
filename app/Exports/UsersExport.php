<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<User>
 */
class UsersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return Collection<int, User>
     */
    public function collection(): Collection
    {
        return User::query()->with('roles:id,name')->orderBy('id')->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['name', 'email', 'roles'];
    }

    /**
     * @param  User  $user
     * @return array<int, string>
     */
    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->roles->pluck('name')->implode(', '),
        ];
    }
}
