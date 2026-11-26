<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    /**
     * Display the list of available database backup files.
     */
    public function index(): Response
    {
        $backupDir = $this->backupDirectory();

        $files = File::isDirectory($backupDir)
            ? collect(File::files($backupDir))
                ->filter(fn (\SplFileInfo $file): bool => in_array(strtolower($file->getExtension()), ['sqlite', 'sql', 'zip'], true))
                ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime())
                ->values()
                ->map(fn (\SplFileInfo $file): array => [
                    'filename' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
                ])
                ->all()
            : [];

        return Inertia::render('backups/Index', [
            'backups' => $files,
            'driver' => config('database.default'),
        ]);
    }

    /**
     * Create a new database backup file for the active database connection.
     */
    public function store(): RedirectResponse
    {
        $backupDir = $this->backupDirectory();
        File::ensureDirectoryExists($backupDir);

        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver", $connection);
        $timestamp = now()->format('Y-m-d_His');

        if ($driver === 'sqlite') {
            $databasePath = (string) config("database.connections.{$connection}.database");

            if (! File::exists($databasePath)) {
                return back()->withErrors([
                    'backup' => 'SQLite database file not found.',
                ]);
            }

            $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup-{$timestamp}.sqlite";
            File::copy($databasePath, $targetPath);
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup-{$timestamp}.sql";
            $config = config("database.connections.{$connection}");

            $result = Process::run([
                'mysqldump',
                '--host='.(string) ($config['host'] ?? '127.0.0.1'),
                '--port='.(string) ($config['port'] ?? '3306'),
                '--user='.(string) ($config['username'] ?? ''),
                '--password='.(string) ($config['password'] ?? ''),
                '--result-file='.$targetPath,
                (string) ($config['database'] ?? ''),
            ]);

            if ($result->failed()) {
                File::delete($targetPath);

                return back()->withErrors([
                    'backup' => 'Failed to run mysqldump: '.trim($result->errorOutput()),
                ]);
            }
        } elseif ($driver === 'pgsql') {
            $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup-{$timestamp}.sql";
            $config = config("database.connections.{$connection}");

            $result = Process::env([
                'PGPASSWORD' => (string) ($config['password'] ?? ''),
            ])->run([
                'pg_dump',
                '-h', (string) ($config['host'] ?? '127.0.0.1'),
                '-p', (string) ($config['port'] ?? '5432'),
                '-U', (string) ($config['username'] ?? ''),
                '-f', $targetPath,
                (string) ($config['database'] ?? ''),
            ]);

            if ($result->failed()) {
                File::delete($targetPath);

                return back()->withErrors([
                    'backup' => 'Failed to run pg_dump: '.trim($result->errorOutput()),
                ]);
            }
        } else {
            return back()->withErrors([
                'backup' => "Unsupported database driver: {$driver}",
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Database backup created.')]);

        return back();
    }

    /**
     * Download an existing database backup file, preventing directory traversal.
     */
    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->resolveBackupPath($filename);

        abort_unless(File::exists($path), 404);

        return response()->download($path, basename($path));
    }

    /**
     * Delete an existing database backup file from storage.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $path = $this->resolveBackupPath($filename);

        if (File::exists($path)) {
            File::delete($path);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Backup deleted.')]);

        return back();
    }

    /**
     * Get the absolute path to the database backups directory.
     */
    private function backupDirectory(): string
    {
        return storage_path('app/backups');
    }

    /**
     * Resolve a sanitized backup filename to its full path inside the backup directory.
     */
    private function resolveBackupPath(string $filename): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR.basename($filename);
    }

    /**
     * Format a byte count into a human-readable size string.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }
}
