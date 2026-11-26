<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow
{
    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));

            if ($name === '' || $email === '') {
                continue;
            }

            $password = trim((string) ($row['password'] ?? ''));

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password !== '' ? $password : 'password',
                    'email_verified_at' => now(),
                ],
            );

            $rolesRaw = trim((string) ($row['roles'] ?? ''));

            if ($rolesRaw !== '') {
                $roleNames = array_filter(array_map('trim', explode(',', $rolesRaw)));
                $validRoles = Role::query()->whereIn('name', $roleNames)->pluck('name')->all();
                $user->syncRoles($validRoles);
            }
        }
    }
}
