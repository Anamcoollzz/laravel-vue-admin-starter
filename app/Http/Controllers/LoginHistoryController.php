<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginHistoryController extends Controller
{
    /**
     * Display a paginated list of user login, failed login, and logout history entries.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $status = strtolower($request->string('status')->trim()->value());

        $histories = LoginHistory::query()
            ->with('user:id,name,email')
            ->when($status !== '' && in_array($status, ['success', 'failed', 'logout'], true), function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('email', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('browser', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (LoginHistory $entry): array => [
                'id' => $entry->id,
                'user_name' => $entry->user?->name,
                'email' => $entry->email ?? $entry->user?->email,
                'status' => $entry->status,
                'ip_address' => $entry->ip_address,
                'browser' => $entry->browser,
                'platform' => $entry->platform,
                'created_at' => $entry->created_at?->format('Y-m-d H:i:s'),
                'created_at_human' => $entry->created_at?->diffForHumans(),
            ]);

        return Inertia::render('login-history/Index', [
            'histories' => $histories,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Delete a single login history entry.
     */
    public function destroy(LoginHistory $loginHistory): RedirectResponse
    {
        $loginHistory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Login history entry deleted.')]);

        return back();
    }

    /**
     * Delete all recorded login history entries.
     */
    public function clear(): RedirectResponse
    {
        LoginHistory::query()->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All login history cleared.')]);

        return back();
    }
}
