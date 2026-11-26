<?php

namespace App\Http\Controllers;

use App\Models\IpBlacklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IpBlacklistController extends Controller
{
    /**
     * Display a paginated list of blacklisted IP addresses with optional search filter.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $entries = IpBlacklist::query()
            ->with('creator:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('ip_address', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (IpBlacklist $entry): array => [
                'id' => $entry->id,
                'ip_address' => $entry->ip_address,
                'reason' => $entry->reason,
                'is_active' => $entry->is_active,
                'expires_at' => $entry->expires_at?->format('Y-m-d\TH:i'),
                'expires_at_display' => $entry->expires_at?->format('Y-m-d H:i'),
                'creator' => $entry->creator?->name,
                'created_at' => $entry->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('ip-blacklist/Index', [
            'entries' => $entries,
            'currentIp' => $request->ip(),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly blacklisted IP address, preventing the current user from blocking their own IP.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ip_address' => [
                'required',
                'ip',
                Rule::unique(IpBlacklist::class, 'ip_address'),
                Rule::notIn([$request->ip()]),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        IpBlacklist::query()->create([
            'ip_address' => $validated['ip_address'],
            'reason' => $validated['reason'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'expires_at' => $validated['expires_at'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('IP address added to blacklist.')]);

        return back();
    }

    /**
     * Update an existing IP blacklist entry, preventing active blocking of the current user's own IP.
     */
    public function update(Request $request, IpBlacklist $ipBlacklist): RedirectResponse
    {
        $isActive = (bool) $request->boolean('is_active');

        $validated = $request->validate([
            'ip_address' => [
                'required',
                'ip',
                Rule::unique(IpBlacklist::class, 'ip_address')->ignore($ipBlacklist->id),
                ...($isActive ? [Rule::notIn([$request->ip()])] : []),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $ipBlacklist->update([
            'ip_address' => $validated['ip_address'],
            'reason' => $validated['reason'] ?? null,
            'is_active' => $isActive,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('IP blacklist entry updated.')]);

        return back();
    }

    /**
     * Remove an IP address from the blacklist.
     */
    public function destroy(IpBlacklist $ipBlacklist): RedirectResponse
    {
        $ipBlacklist->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('IP address removed from blacklist.')]);

        return back();
    }
}
