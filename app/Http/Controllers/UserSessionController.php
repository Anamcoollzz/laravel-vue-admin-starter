<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

class UserSessionController extends Controller
{
    /**
     * Display a paginated list of active database sessions with user info, browser, platform, and last activity.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $currentSessionId = $request->session()->getId();

        $query = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->whereNotNull('sessions.user_id')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('sessions.ip_address', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('sessions.last_activity');

        $sessions = $query->paginate(15)
            ->withQueryString()
            ->through(function (stdClass $row) use ($currentSessionId): array {
                $userAgent = (string) ($row->user_agent ?? '');
                $lastActivity = Carbon::createFromTimestamp((int) $row->last_activity);

                return [
                    'id' => (string) $row->id,
                    'user_id' => $row->user_id !== null ? (int) $row->user_id : null,
                    'user_name' => $row->user_name !== null ? (string) $row->user_name : null,
                    'user_email' => $row->user_email !== null ? (string) $row->user_email : null,
                    'ip_address' => $row->ip_address !== null ? (string) $row->ip_address : null,
                    'browser' => $this->detectBrowser($userAgent),
                    'platform' => $this->detectPlatform($userAgent),
                    'is_current' => (string) $row->id === $currentSessionId,
                    'last_activity' => $lastActivity->format('Y-m-d H:i:s'),
                    'last_activity_human' => $lastActivity->diffForHumans(),
                ];
            });

        return Inertia::render('sessions/Index', [
            'sessions' => $sessions,
            'driver' => (string) config('session.driver'),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Revoke a single active user session, preventing revoking the currently active session.
     */
    public function destroy(Request $request, string $sessionId): RedirectResponse
    {
        if ($sessionId === $request->session()->getId()) {
            return back()->withErrors([
                'session' => 'You cannot revoke your current active session.',
            ]);
        }

        DB::table('sessions')->where('id', $sessionId)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Session revoked.')]);

        return back();
    }

    /**
     * Revoke all active sessions except the currently authenticated admin's session.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $currentSessionId = $request->session()->getId();

        DB::table('sessions')
            ->where('id', '!=', $currentSessionId)
            ->whereNotNull('user_id')
            ->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('All other sessions revoked.')]);

        return back();
    }

    /**
     * Detect the browser name from a User-Agent string.
     */
    private function detectBrowser(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'Edge/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            $userAgent === '' => 'Unknown',
            default => 'Other',
        };
    }

    /**
     * Detect the operating system / platform from a User-Agent string.
     */
    private function detectPlatform(string $userAgent): string
    {
        return match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Macintosh') || str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            $userAgent === '' => 'Unknown',
            default => 'Other',
        };
    }
}
