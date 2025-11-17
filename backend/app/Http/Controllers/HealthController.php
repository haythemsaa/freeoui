<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Exception;

class HealthController extends Controller
{
    /**
     * Basic health check endpoint
     * Returns 200 OK if application is running
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'FreeOui API',
        ]);
    }

    /**
     * Detailed readiness check
     * Checks database, Redis, and other critical dependencies
     */
    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            
            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity
     */
    protected function checkRedis(): array
    {
        try {
            Redis::connection()->ping();
            
            return [
                'status' => 'ok',
                'message' => 'Redis connection successful',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Redis connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage availability
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path('app');
            
            if (!is_writable($path)) {
                return [
                    'status' => 'error',
                    'message' => 'Storage is not writable',
                ];
            }
            
            return [
                'status' => 'ok',
                'message' => 'Storage is writable',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
