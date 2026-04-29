<?php

namespace App\Http\Middleware;

use App\Support\Security\RateLimitGuard;
use App\Support\Security\RateLimitPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SmartThrottle
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $policy = 'api'): Response
    {
        $result = app(RateLimitGuard::class)->attempt(
            RateLimitPolicy::for($policy, $request),
        );

        if (! $result->allowed) {
            $message = "Too many requests. Try again in {$result->retryAfterSeconds} seconds.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'retry_after' => $result->retryAfterSeconds,
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            return back()->withErrors(['rate_limit' => $message])->withInput();
        }

        return $next($request);
    }
}
