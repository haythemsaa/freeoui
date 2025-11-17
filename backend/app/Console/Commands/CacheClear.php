<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheClear extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'freeoui:cache-clear
                            {--all : Clear all caches}';

    /**
     * The console command description.
     */
    protected $description = 'Clear all FreeOui caches (config, route, view, application)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Clearing FreeOui caches...');

        // Clear config cache
        $this->info('Clearing config cache...');
        Artisan::call('config:clear');

        // Clear route cache
        $this->info('Clearing route cache...');
        Artisan::call('route:clear');

        // Clear view cache
        $this->info('Clearing view cache...');
        Artisan::call('view:clear');

        // Clear application cache
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');

        if ($this->option('all')) {
            // Clear compiled classes
            $this->info('Clearing compiled classes...');
            Artisan::call('clear-compiled');

            // Clear Redis cache manually
            $this->info('Flushing Redis cache...');
            try {
                Cache::flush();
                $this->info('✓ Redis cache flushed');
            } catch (\Exception $e) {
                $this->error('✗ Failed to flush Redis: ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('✓ All caches cleared successfully!');

        return self::SUCCESS;
    }
}
