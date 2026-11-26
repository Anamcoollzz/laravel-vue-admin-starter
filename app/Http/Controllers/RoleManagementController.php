<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RoleManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
                'users_count' => $role->users_count,
                'created_at' => $role->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('name')->pluck('name')->values()->all(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back();
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back();
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();

        return back();
    }

    public function export(): BinaryFileResponse
    {
        $tempPath = storage_path('app/roles-export-'.uniqid().'.xlsx');

        $writer = SimpleExcelWriter::create($tempPath);

        Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get()
            ->each(function (Role $role) use ($writer): void {
                $writer->addRow([
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->implode(', '),
                ]);
            });

        $writer->close();

        return response()->download($tempPath, 'roles.xlsx')->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        SimpleExcelReader::create($file->getRealPath(), $extension)
            ->getRows()
            ->each(function (array $row): void {
                $name = trim((string) ($row['name'] ?? ''));

                if ($name === '') {
                    return;
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
            });

        return back();
    }
}
