<?php

namespace App\Http\Controllers;

use App\Models\RequestLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestLogController extends Controller
{
    /**
     * Display a paginated list of recorded HTTP request logs with optional search and method filters.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $method = strtoupper($request->string('method')->trim()->value());

        $logs = RequestLog::query()
            ->with('user:id,name,email')
            ->when($method !== '' && in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true), function ($query) use ($method): void {
                $query->where('method', $method);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('path', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('browser', 'like', "%{$search}%")
                        ->orWhere('route_name', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (RequestLog $log): array => [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user' => $log->user ? [
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'roles' => $log->roles ?? [],
                'ip_address' => $log->ip_address,
                'method' => $log->method,
                'path' => $log->path,
                'route_name' => $log->route_name,
                'status_code' => $log->status_code,
                'duration_ms' => $log->duration_ms,
                'browser' => $log->browser,
                'platform' => $log->platform,
                'user_agent' => $log->user_agent,
                'request_payload' => $log->request_payload,
                'referer' => $log->referer,
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('logs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'method' => $method,
            ],
        ]);
    }

    /**
     * Delete a single request log entry.
     */
    public function destroy(RequestLog $requestLog): RedirectResponse
    {
        $requestLog->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request log deleted.')]);

        return back();
    }

    /**
     * Delete all recorded request log entries from the database.
     */
    public function clear(): RedirectResponse
    {
        RequestLog::query()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All request logs cleared.')]);

        return back();
    }
}
