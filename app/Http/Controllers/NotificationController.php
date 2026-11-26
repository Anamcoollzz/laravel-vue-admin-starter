<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark a single notification belonging to the authenticated user as read.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()?->notifications()->where('id', $id)->first();

        if ($notification !== null && $notification->read_at === null) {
            $notification->markAsRead();
        }

        $redirectUrl = is_array($notification?->data) ? ($notification->data['url'] ?? null) : null;

        if (is_string($redirectUrl) && str_starts_with($redirectUrl, '/')) {
            return redirect($redirectUrl);
        }

        return back();
    }

    /**
     * Mark all unread notifications for the authenticated user as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return back();
    }
}
