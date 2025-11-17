<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class ProcessNoShows extends Command
{
    protected $signature = 'bookings:process-no-shows';

    protected $description = 'Mark bookings as no-show if not completed';

    public function handle(BookingService $bookingService): int
    {
        $this->info('Processing no-shows...');

        $count = $bookingService->processNoShows();

        $this->info("Marked {$count} bookings as no-show.");

        return Command::SUCCESS;
    }
}
