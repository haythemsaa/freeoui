<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'health:check
                            {--json : Output as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Check system health (database, redis, storage)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = [];
        $allHealthy = true;

        // Check Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection OK',
            ];
            $this->info('✓ Database: OK');
        } catch (\Exception $e) {
            $checks['database'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $this->error('✗ Database: FAILED');
            $allHealthy = false;
        }

        // Check Redis
        try {
            Redis::ping();
            $checks['redis'] = [
                'status' => 'healthy',
                'message' => 'Redis connection OK',
            ];
            $this->info('✓ Redis: OK');
        } catch (\Exception $e) {
            $checks['redis'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $this->error('✗ Redis: FAILED');
            $allHealthy = false;
        }

        // Check Storage
        try {
            $testFile = storage_path('framework/cache/health-check.txt');
            file_put_contents($testFile, 'test');
            unlink($testFile);
            $checks['storage'] = [
                'status' => 'healthy',
                'message' => 'Storage writable',
            ];
            $this->info('✓ Storage: OK');
        } catch (\Exception $e) {
            $checks['storage'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $this->error('✗ Storage: FAILED');
            $allHealthy = false;
        }

        // Check Queue
        try {
            $queueSize = Redis::llen('queues:default');
            $checks['queue'] = [
                'status' => 'healthy',
                'message' => "Queue size: {$queueSize}",
                'size' => $queueSize,
            ];
            $this->info("✓ Queue: OK (size: {$queueSize})");
        } catch (\Exception $e) {
            $checks['queue'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $this->error('✗ Queue: FAILED');
            $allHealthy = false;
        }

        // Check PostGIS
        try {
            $hasPostGIS = DB::select("SELECT PostGIS_version()");
            $checks['postgis'] = [
                'status' => 'healthy',
                'message' => 'PostGIS extension available',
                'version' => $hasPostGIS[0]->postgis_version ?? 'unknown',
            ];
            $this->info('✓ PostGIS: OK');
        } catch (\Exception $e) {
            $checks['postgis'] = [
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
            ];
            $this->error('✗ PostGIS: FAILED');
            $allHealthy = false;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'status' => $allHealthy ? 'healthy' : 'unhealthy',
                'checks' => $checks,
                'timestamp' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT));
        }

        $this->newLine();
        if ($allHealthy) {
            $this->info('All systems healthy!');
            return self::SUCCESS;
        } else {
            $this->error('Some systems are unhealthy!');
            return self::FAILURE;
        }
    }
}
