<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appSettings' => [
                'logo' => config('app.logo'),
                'favicon' => config('app.favicon'),
            ],
            'locale' => fn (): string => app()->getLocale(),
            'auth' => [
                'user' => $request->user(),
                'roles' => fn (): array => $request->user()?->getRoleNames()->toArray() ?? [],
                'permissions' => fn (): array => $request->user()?->getAllPermissions()->pluck('name')->toArray() ?? [],
            ],
            'notifications' => fn (): array => $request->user()
                ? [
                    'unread_count' => $request->user()->unreadNotifications()->count(),
                    'items' => $request->user()
                        ->notifications()
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->map(fn (DatabaseNotification $notification): array => [
                            'id' => $notification->id,
                            'type' => (string) ($notification->data['type'] ?? 'info'),
                            'title' => (string) ($notification->data['title'] ?? ''),
                            'body' => (string) ($notification->data['body'] ?? ''),
                            'url' => (string) ($notification->data['url'] ?? ''),
                            'read_at' => $notification->read_at?->toISOString(),
                            'created_at' => $notification->created_at?->diffForHumans(),
                        ])
                        ->all(),
                ]
                : ['unread_count' => 0, 'items' => []],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
