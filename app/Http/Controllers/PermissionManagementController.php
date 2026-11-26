<?php

namespace App\Http\Controllers;

use App\Exports\ImportTemplateExport;
use App\Exports\PermissionsExport;
use App\Imports\PermissionsImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PermissionManagementController extends Controller
{
    /**
     * Display a paginated list of permissions with their assigned role counts, filtered by an optional name search.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $permissions = Permission::query()
            ->withCount('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'roles_count' => $permission->roles_count,
                'created_at' => $permission->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('permissions/Index', [
            'permissions' => $permissions,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created permission for the web guard.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')],
        ]);

        Permission::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        return back();
    }

    /**
     * Update the given permission's name.
     */
    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update([
            'name' => $validated['name'],
        ]);

        return back();
    }

    /**
     * Delete the given permission.
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();

        return back();
    }

    /**
     * Download all permissions as an Excel file.
     */
    public function export(): BinaryFileResponse
    {
        return Excel::download(new PermissionsExport, 'permissions.xlsx');
    }

    /**
     * Download a sample Excel template for importing permissions.
     */
    public function template(): BinaryFileResponse
    {
        return Excel::download(new ImportTemplateExport(
            ['name', 'guard_name'],
            [
                ['reports.view', 'web'],
                ['reports.export', 'web'],
            ],
        ), 'permissions-import-template.xlsx');
    }

    /**
     * Import permissions from an uploaded Excel/CSV file, skipping ones that already exist.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ]);

        Excel::import(new PermissionsImport, $request->file('file'));

        return back();
    }
}
