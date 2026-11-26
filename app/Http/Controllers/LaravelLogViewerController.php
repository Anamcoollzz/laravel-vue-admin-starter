<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaravelLogViewerController extends Controller
{
    /**
     * Display parsed log entries from a selected Laravel log file with level and search filters.
     */
    public function index(Request $request): Response
    {
        $logDir = storage_path('logs');
        $files = $this->listLogFiles($logDir);

        $selectedFile = basename($request->string('file')->value());
        $fileNames = array_column($files, 'name');

        if ($selectedFile === '' || ! in_array($selectedFile, $fileNames, true)) {
            $selectedFile = in_array('laravel.log', $fileNames, true)
                ? 'laravel.log'
                : ($fileNames[0] ?? '');
        }

        $levelFilter = strtoupper($request->string('level')->trim()->value());
        $search = $request->string('search')->trim()->value();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $entries = [];

        if ($selectedFile !== '') {
            $fullPath = $logDir.DIRECTORY_SEPARATOR.$selectedFile;
            $entries = $this->parseLogFile($fullPath);
        }

        $filtered = collect($entries)
            ->when($levelFilter !== '', fn ($col) => $col->where('level', $levelFilter))
            ->when($search !== '', function ($col) use ($search) {
                $needle = strtolower($search);

                return $col->filter(fn (array $entry): bool => str_contains(strtolower($entry['message']), $needle)
                    || str_contains(strtolower($entry['stack']), $needle)
                    || str_contains(strtolower($entry['datetime']), $needle));
            })
            ->values();

        $total = $filtered->count();
        $items = $filtered->forPage($page, $perPage)->values()->all();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return Inertia::render('logs/LaravelLogs', [
            'files' => $files,
            'selectedFile' => $selectedFile,
            'entries' => $paginator,
            'filters' => [
                'level' => $levelFilter,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Download the selected Laravel log file, preventing directory traversal.
     */
    public function download(Request $request): BinaryFileResponse
    {
        $path = $this->resolveLogPath($request->string('file')->value());

        abort_unless($path !== null && File::exists($path), 404);

        return response()->download($path, basename($path));
    }

    /**
     * Truncate the contents of the selected Laravel log file.
     */
    public function clear(Request $request): RedirectResponse
    {
        $path = $this->resolveLogPath($request->string('file')->value());

        if ($path !== null && File::exists($path)) {
            File::put($path, '');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Log file cleared.')]);

        return back();
    }

    /**
     * Delete the selected Laravel log file from storage.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $path = $this->resolveLogPath($request->string('file')->value());

        if ($path !== null && File::exists($path)) {
            File::delete($path);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Log file deleted.')]);

        return to_route('laravel-logs.index');
    }

    /**
     * List all .log files inside the logs directory sorted by most recently modified.
     *
     * @return array<int, array{name: string, size: string, last_modified: string}>
     */
    private function listLogFiles(string $logDir): array
    {
        if (! File::isDirectory($logDir)) {
            return [];
        }

        return collect(File::files($logDir))
            ->filter(fn (\SplFileInfo $file): bool => strtolower($file->getExtension()) === 'log')
            ->sortByDesc(fn (\SplFileInfo $file): int => $file->getMTime())
            ->values()
            ->map(fn (\SplFileInfo $file): array => [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'last_modified' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
            ])
            ->all();
    }

    /**
     * Parse a Laravel Monolog file into structured entries ordered newest first.
     *
     * @return array<int, array{id: int, datetime: string, env: string, level: string, message: string, stack: string}>
     */
    private function parseLogFile(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        // Read up to the last 2 MB to keep memory usage bounded on large log files.
        $maxBytes = 2 * 1024 * 1024;
        $size = File::size($path);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        if ($size > $maxBytes) {
            fseek($handle, -$maxBytes, SEEK_END);
        }

        $content = (string) stream_get_contents($handle);
        fclose($handle);

        $pattern = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.([A-Z]+): (.*)$/m';
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        $count = count($matches[0]);
        $entries = [];

        for ($i = 0; $i < $count; $i++) {
            $matchEnd = $matches[0][$i][1] + strlen($matches[0][$i][0]);
            $nextStart = $i + 1 < $count ? $matches[0][$i + 1][1] : strlen($content);
            $stack = trim(substr($content, $matchEnd, $nextStart - $matchEnd));

            $entries[] = [
                'id' => $i + 1,
                'datetime' => $matches[1][$i][0],
                'env' => $matches[2][$i][0],
                'level' => strtoupper($matches[3][$i][0]),
                'message' => trim($matches[4][$i][0]),
                'stack' => $stack,
            ];
        }

        return array_reverse($entries);
    }

    /**
     * Resolve a sanitized .log filename to its full path inside storage/logs.
     */
    private function resolveLogPath(string $filename): ?string
    {
        $safe = basename(trim($filename));

        if ($safe === '' || ! str_ends_with(strtolower($safe), '.log')) {
            return null;
        }

        return storage_path('logs'.DIRECTORY_SEPARATOR.$safe);
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
