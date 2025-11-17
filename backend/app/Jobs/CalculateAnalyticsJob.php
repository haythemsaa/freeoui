<?php

namespace App\Jobs;

use App\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalculateAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    public function __construct(
        public string $metricType,
        public ?Carbon $date = null
    ) {
        $this->date = $date ?? Carbon::today();
    }

    public function handle(AnalyticsService $analyticsService): void
    {
        try {
            Log::info('Calculating analytics', [
                'metric_type' => $this->metricType,
                'date' => $this->date->toDateString(),
            ]);

            match ($this->metricType) {
                'dau' => $analyticsService->calculateDAU($this->date),
                'wau' => $analyticsService->calculateWAU($this->date),
                'mau' => $analyticsService->calculateMAU($this->date),
                'retention' => $analyticsService->getRetentionCohort($this->date->startOfMonth(), 12),
                default => null,
            };

            Log::info('Analytics calculated', [
                'metric_type' => $this->metricType,
            ]);
        } catch (\Exception $e) {
            Log::error('Analytics calculation exception', [
                'metric_type' => $this->metricType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Analytics job failed', [
            'metric_type' => $this->metricType,
            'error' => $exception->getMessage(),
        ]);
    }
}
