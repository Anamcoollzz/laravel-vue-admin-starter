<?php

namespace App\Http\Middleware;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRequestMiddleware
{
    /**
     * Sensitive input keys that must be redacted before storing request payloads.
     *
     * @var list<string>
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        '_token',
        '_method',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Handle an incoming request and record request metadata to the database.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        if ($this->shouldSkip($request)) {
            return $response;
        }

        try {
            $user = $request->user();
            $userAgent = (string) ($request->userAgent() ?? '');

            RequestLog::query()->create([
                'user_id' => $user?->id,
                'roles' => $user ? $user->getRoleNames()->values()->all() : [],
                'ip_address' => $request->ip(),
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'route_name' => $request->route()?->getName(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'browser' => $this->detectBrowser($userAgent),
                'platform' => $this->detectPlatform($userAgent),
                'user_agent' => $userAgent !== '' ? $userAgent : null,
                'request_payload' => $this->sanitizePayload($request->except(self::REDACTED_KEYS)),
                'referer' => $request->headers->get('referer'),
            ]);
        } catch (Throwable) {
            // Never interrupt the application response if logging fails.
        }

        return $response;
    }

    /**
     * Determine whether the request should be excluded from request logging.
     */
    private function shouldSkip(Request $request): bool
    {
        if ($request->is('up', 'build/*', '_boost/*')) {
            return true;
        }

        return $request->isMethod('GET') && $request->is('app/request-logs');
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
            str_contains($userAgent, 'Postman') => 'Postman',
            str_contains($userAgent, 'curl') => 'cURL',
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

    /**
     * Recursively sanitize request payload values so they are JSON-safe.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function sanitizePayload(array $payload): ?array
    {
        if ($payload === []) {
            return null;
        }

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), self::REDACTED_KEYS, true)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_object($value)) {
                $sanitized[$key] = '[OBJECT]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value);
            } else {
                $sanitized[$key] = is_string($value) && strlen($value) > 500
                    ? substr($value, 0, 500).'...'
                    : $value;
            }
        }

        return $sanitized;
    }
}
