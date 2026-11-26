<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard along with currently active announcements.
     */
    public function index(): Response
    {
        $announcements = Announcement::query()
            ->visible()
            ->with('author:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Announcement $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'content' => $item->content,
                'type' => $item->type,
                'author' => $item->author?->name,
                'created_at' => $item->created_at?->format('Y-m-d H:i'),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'announcements' => $announcements,
        ]);
    }
}
