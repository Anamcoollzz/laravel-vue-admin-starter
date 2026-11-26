<?php

namespace App\Http\Middleware;

use App\Models\IpBlacklist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckIpBlacklistMiddleware
{
    /**
     * Block incoming requests whose client IP matches an active IP blacklist entry.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if ($ip !== null) {
            try {
                $isBlocked = IpBlacklist::query()
                    ->active()
                    ->where('ip_address', $ip)
                    ->exists();

                if ($isBlocked) {
                    abort(403, __('Your IP address has been blocked.'));
                }
            } catch (Throwable) {
                // Allow requests through if the blacklist table is not yet migrated.
            }
        }

        return $next($request);
    }
}
