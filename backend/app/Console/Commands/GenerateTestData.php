<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateTestData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'freeoui:generate-test-data
                            {--fresh : Drop all tables and migrate fresh}';

    /**
     * The console command description.
     */
    protected $description = 'Generate comprehensive test data for FreeOui';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('fresh')) {
            if (!$this->confirm('This will drop all tables and data. Are you sure?')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            $this->info('Running fresh migrations...');
            Artisan::call('migrate:fresh');
            $this->info(Artisan::output());
        }

        $this->info('Generating test data...');

        // Seed categories
        $this->info('Seeding categories...');
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);

        // Seed merchants
        $this->info('Seeding merchants...');
        Artisan::call('db:seed', ['--class' => 'MerchantSeeder']);

        // Seed users
        $this->info('Seeding users...');
        Artisan::call('db:seed', ['--class' => 'UserSeeder']);

        // Seed advantages
        $this->info('Seeding advantages...');
        Artisan::call('db:seed', ['--class' => 'AdvantageSeeder']);

        $this->newLine();
        $this->info('✓ Test data generated successfully!');
        $this->newLine();

        $this->info('Summary:');
        $this->info('- Categories: ' . \App\Models\Category::count());
        $this->info('- Merchants: ' . \App\Models\Merchant::count());
        $this->info('- Users: ' . \App\Models\User::count());
        $this->info('- Advantages: ' . \App\Models\Advantage::count());

        return self::SUCCESS;
    }
}
