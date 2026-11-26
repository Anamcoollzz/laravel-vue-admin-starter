<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Throwable;

class RecordLoginHistory
{
    /**
     * Record a successful login event.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        $this->record('success', $user?->id, $user?->email);
    }

    /**
     * Record a failed login attempt event.
     */
    public function handleFailed(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;
        $email = $user?->email;

        if ($email === null && isset($event->credentials['email']) && is_string($event->credentials['email'])) {
            $email = $event->credentials['email'];
        }

        $this->record('failed', $user?->id, $email);
    }

    /**
     * Record a user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        $this->record('logout', $user?->id, $user?->email);
    }

    /**
     * Persist a login history entry with IP address and parsed User-Agent metadata.
     */
    private function record(string $status, ?int $userId, ?string $email): void
    {
        try {
            $request = request();
            $userAgent = (string) ($request->userAgent() ?? '');

            LoginHistory::query()->create([
                'user_id' => $userId,
                'email' => $email,
                'status' => $status,
                'ip_address' => $request->ip(),
                'browser' => $this->detectBrowser($userAgent),
                'platform' => $this->detectPlatform($userAgent),
                'user_agent' => $userAgent !== '' ? $userAgent : null,
            ]);
        } catch (Throwable) {
            // Never interrupt authentication flow if history logging fails.
        }
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
