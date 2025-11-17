<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $request->headers->set('X-Request-ID', $requestId);

        $startTime = microtime(true);

        // Log request
        $this->logRequest($request, $requestId);

        $response = $next($request);

        // Log response
        $duration = microtime(true) - $startTime;
        $this->logResponse($request, $response, $requestId, $duration);

        // Add request ID to response headers
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Log incoming request
     */
    protected function logRequest(Request $request, string $requestId): void
    {
        $data = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
        ];

        // Log request body for non-GET requests (excluding sensitive data)
        if (!$request->isMethod('GET')) {
            $body = $request->except(['password', 'password_confirmation', 'token']);
            if (!empty($body)) {
                $data['body'] = $body;
            }
        }

        Log::info('Incoming request', $data);
    }

    /**
     * Log outgoing response
     */
    protected function logResponse(
        Request $request,
        Response $response,
        string $requestId,
        float $duration
    ): void {
        $durationMs = round($duration * 1000, 2);

        $data = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ];

        $level = $this->getLogLevel($response->getStatusCode());

        Log::log($level, 'Outgoing response', $data);

        // Log slow requests
        if ($durationMs > 1000) {
            Log::warning('Slow request detected', array_merge($data, [
                'threshold_ms' => 1000,
            ]));
        }

        // Log errors with response body
        if ($response->getStatusCode() >= 500) {
            $data['response_body'] = $response->getContent();
            Log::error('Server error response', $data);
        }
    }

    /**
     * Get log level based on status code
     */
    protected function getLogLevel(int $statusCode): string
    {
        return match(true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            default => 'info',
        };
    }
}
