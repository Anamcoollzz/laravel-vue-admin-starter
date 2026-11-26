<?php

namespace App\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DatabaseExplorerController extends Controller
{
    /**
     * Display a list of all database tables with their column and row counts.
     */
    public function index(): Response
    {
        $tables = collect(Schema::getTables())
            ->map(function (array $table): array {
                $name = (string) $table['name'];

                return [
                    'name' => $name,
                    'columns_count' => count(Schema::getColumns($name)),
                    'rows_count' => DB::table($name)->count(),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();

        return Inertia::render('database/Index', [
            'tables' => $tables,
            'driver' => config('database.default'),
        ]);
    }

    /**
     * Display the column structure and paginated rows of a specific database table.
     */
    public function show(Request $request, string $table): Response
    {
        $this->ensureTableExists($table);

        $columns = collect(Schema::getColumns($table))
            ->map(fn (array $col): array => [
                'name' => (string) $col['name'],
                'type_name' => (string) ($col['type_name'] ?? $col['type'] ?? ''),
                'type' => (string) ($col['type'] ?? ''),
                'nullable' => (bool) ($col['nullable'] ?? false),
                'default' => $col['default'] !== null ? (string) $col['default'] : null,
                'auto_increment' => (bool) ($col['auto_increment'] ?? false),
            ])
            ->values()
            ->all();

        $primaryKey = $this->detectPrimaryKey($table, $columns);
        $search = $request->string('search')->trim()->value();

        $query = DB::table($table);

        if ($search !== '') {
            $query->where(function ($subQuery) use ($columns, $search): void {
                foreach ($columns as $column) {
                    $subQuery->orWhere($column['name'], 'like', "%{$search}%");
                }
            });
        }

        if ($primaryKey !== null) {
            $query->orderByDesc($primaryKey);
        }

        $rows = $query->paginate(15)->withQueryString();

        return Inertia::render('database/Show', [
            'table' => $table,
            'primaryKey' => $primaryKey,
            'columns' => $columns,
            'rows' => $rows,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Rename an existing column on the specified database table.
     */
    public function renameColumn(Request $request, string $table, string $column): RedirectResponse
    {
        $this->ensureTableExists($table);
        $this->ensureColumnExists($table, $column);

        $validated = $request->validate([
            'new_name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/'],
        ]);

        $newName = $validated['new_name'];

        if ($newName === $column) {
            return back();
        }

        if (Schema::hasColumn($table, $newName)) {
            return back()->withErrors([
                'new_name' => "Column '{$newName}' already exists.",
            ]);
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $newName): void {
                $blueprint->renameColumn($column, $newName);
            });
        } catch (Throwable $e) {
            return back()->withErrors([
                'new_name' => $e->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column renamed.')]);

        return back();
    }

    /**
     * Drop a non-primary-key column from the specified database table.
     */
    public function dropColumn(string $table, string $column): RedirectResponse
    {
        $this->ensureTableExists($table);
        $this->ensureColumnExists($table, $column);

        $columns = Schema::getColumns($table);
        $primaryKey = $this->detectPrimaryKey($table, $columns);

        if ($primaryKey === $column) {
            return back()->withErrors([
                'column' => 'Primary key column cannot be dropped.',
            ]);
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column): void {
                $blueprint->dropColumn($column);
            });
        } catch (Throwable $e) {
            return back()->withErrors([
                'column' => $e->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Column deleted.')]);

        return back();
    }

    /**
     * Update a single row in the specified database table by its primary key.
     */
    public function updateRow(Request $request, string $table, string $id): RedirectResponse
    {
        $this->ensureTableExists($table);

        $columns = Schema::getColumns($table);
        $primaryKey = $this->detectPrimaryKey($table, $columns);

        abort_if($primaryKey === null, 422, 'Table has no primary key.');

        $validated = $request->validate([
            'values' => ['required', 'array'],
        ]);

        $allowedColumns = collect($columns)
            ->pluck('name')
            ->reject(fn (string $name): bool => $name === $primaryKey)
            ->values()
            ->all();

        $payload = [];

        foreach ($allowedColumns as $colName) {
            if (array_key_exists($colName, $validated['values'])) {
                $value = $validated['values'][$colName];
                $payload[$colName] = $value === '' ? null : $value;
            }
        }

        try {
            DB::table($table)->where($primaryKey, $id)->update($payload);
        } catch (Throwable $e) {
            return back()->withErrors([
                'row' => $e->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Row updated.')]);

        return back();
    }

    /**
     * Delete a single row from the specified database table by its primary key.
     */
    public function deleteRow(string $table, string $id): RedirectResponse
    {
        $this->ensureTableExists($table);

        $columns = Schema::getColumns($table);
        $primaryKey = $this->detectPrimaryKey($table, $columns);

        abort_if($primaryKey === null, 422, 'Table has no primary key.');

        try {
            DB::table($table)->where($primaryKey, $id)->delete();
        } catch (Throwable $e) {
            return back()->withErrors([
                'row' => $e->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Row deleted.')]);

        return back();
    }

    /**
     * Abort with 404 if the given table name does not exist in the database schema.
     */
    private function ensureTableExists(string $table): void
    {
        $exists = collect(Schema::getTables())
            ->contains(fn (array $item): bool => (string) $item['name'] === $table);

        abort_unless($exists, 404);
    }

    /**
     * Abort with 404 if the given column does not exist on the table.
     */
    private function ensureColumnExists(string $table, string $column): void
    {
        abort_unless(Schema::hasColumn($table, $column), 404);
    }

    /**
     * Detect the single-column primary key for the given table, if any.
     *
     * @param  array<int, array<string, mixed>>  $columns
     */
    private function detectPrimaryKey(string $table, array $columns): ?string
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (! empty($index['primary']) && count($index['columns']) === 1) {
                return (string) $index['columns'][0];
            }
        }

        foreach ($columns as $col) {
            if (($col['name'] ?? null) === 'id') {
                return 'id';
            }
        }

        return null;
    }
}
