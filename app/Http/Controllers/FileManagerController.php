<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileManagerController extends Controller
{
    /**
     * Display the directories and files inside the requested public storage path.
     */
    public function index(Request $request): Response
    {
        $disk = Storage::disk('public');
        $currentPath = $this->sanitizePath($request->string('path')->value());

        if ($currentPath !== '' && ! $disk->directoryExists($currentPath)) {
            $currentPath = '';
        }

        $directories = collect($disk->directories($currentPath))
            ->sort()
            ->values()
            ->map(fn (string $dir): array => [
                'name' => basename($dir),
                'path' => $dir,
            ])
            ->all();

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        $files = collect($disk->files($currentPath))
            ->sort()
            ->values()
            ->map(function (string $file) use ($disk, $imageExtensions): array {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                return [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => $this->formatBytes($disk->size($file)),
                    'url' => '/storage/'.ltrim($file, '/'),
                    'is_image' => in_array($ext, $imageExtensions, true),
                    'last_modified' => Carbon::createFromTimestamp($disk->lastModified($file))->format('Y-m-d H:i'),
                ];
            })
            ->all();

        $breadcrumbs = [];
        if ($currentPath !== '') {
            $accumulated = '';
            foreach (explode('/', $currentPath) as $segment) {
                $accumulated = $accumulated === '' ? $segment : "{$accumulated}/{$segment}";
                $breadcrumbs[] = [
                    'name' => $segment,
                    'path' => $accumulated,
                ];
            }
        }

        return Inertia::render('files/Index', [
            'currentPath' => $currentPath,
            'breadcrumbs' => $breadcrumbs,
            'directories' => $directories,
            'files' => $files,
        ]);
    }

    /**
     * Upload a file into the specified directory on the public storage disk.
     */
    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        $targetDir = $this->sanitizePath((string) ($validated['path'] ?? ''));
        $uploadedFile = $request->file('file');
        $originalName = basename($uploadedFile->getClientOriginalName());

        $uploadedFile->storeAs($targetDir, $originalName, 'public');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File uploaded.')]);

        return back();
    }

    /**
     * Create a new subdirectory inside the specified path on the public storage disk.
     */
    public function createFolder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\- ]+$/'],
            'path' => ['nullable', 'string', 'max:255'],
        ]);

        $parentDir = $this->sanitizePath((string) ($validated['path'] ?? ''));
        $folderName = trim($validated['name']);
        $newPath = $parentDir === '' ? $folderName : "{$parentDir}/{$folderName}";

        Storage::disk('public')->makeDirectory($newPath);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Folder created.')]);

        return back();
    }

    /**
     * Download a file from the public storage disk, preventing directory traversal.
     */
    public function download(Request $request): StreamedResponse
    {
        $path = $this->sanitizePath($request->string('path')->value());
        $disk = Storage::disk('public');

        abort_if($path === '' || ! $disk->exists($path), 404);

        return $disk->download($path, basename($path));
    }

    /**
     * Delete a file or directory from the public storage disk.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:file,directory'],
        ]);

        $target = $this->sanitizePath($validated['path']);
        $disk = Storage::disk('public');

        if ($target === '') {
            return back();
        }

        if ($validated['type'] === 'directory') {
            $disk->deleteDirectory($target);
        } else {
            $disk->delete($target);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item deleted.')]);

        return back();
    }

    /**
     * Sanitize a relative storage path by removing traversal segments and normalizing slashes.
     */
    private function sanitizePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $segments = array_filter(
            explode('/', $normalized),
            fn (string $seg): bool => $seg !== '' && $seg !== '.' && $seg !== '..',
        );

        return implode('/', $segments);
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
