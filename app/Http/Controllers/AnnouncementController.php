<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    /**
     * Display a paginated list of announcements with optional search and type filters.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $type = $request->string('type')->trim()->value();

        $announcements = Announcement::query()
            ->with('author:id,name')
            ->when($type !== '' && in_array($type, ['info', 'success', 'warning', 'danger'], true), function ($query) use ($type): void {
                $query->where('type', $type);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Announcement $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'type' => $item->type,
                'is_active' => $item->is_active,
                'published_at' => $item->published_at?->format('Y-m-d\TH:i'),
                'expires_at' => $item->expires_at?->format('Y-m-d\TH:i'),
                'author' => $item->author?->name,
                'created_at' => $item->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('announcements/Index', [
            'announcements' => $announcements,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    /**
     * Store a newly created announcement authored by the authenticated user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'in:info,success,warning,danger'],
            'is_active' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
        ]);

        Announcement::query()->create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement created.')]);

        return back();
    }

    /**
     * Update an existing announcement's details and visibility settings.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'string', 'in:info,success,warning,danger'],
            'is_active' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
        ]);

        $announcement->update([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement updated.')]);

        return back();
    }

    /**
     * Delete an existing announcement.
     */
    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Announcement deleted.')]);

        return back();
    }
}
