<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = '60:1'): Response
    {
        [$maxAttempts, $decayMinutes] = explode(':', $limit);

        $key = $this->resolveRequestKey($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez réessayer plus tard.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve the rate limiter key for the request
     */
    protected function resolveRequestKey(Request $request): string
    {
        // Use user ID if authenticated, otherwise use IP
        if ($user = $request->user()) {
            return 'rate_limit:user:' . $user->id . ':' . $request->path();
        }

        return 'rate_limit:ip:' . $request->ip() . ':' . $request->path();
    }

    /**
     * Calculate the number of remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - RateLimiter::attempts($key));
    }

    /**
     * Add rate limit headers to the response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }
}
