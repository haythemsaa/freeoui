<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send reminders for upcoming bookings';

    public function handle(BookingService $bookingService): int
    {
        $this->info('Sending booking reminders...');

        $count = $bookingService->sendReminders();

        $this->info("Sent {$count} booking reminders.");

        return Command::SUCCESS;
    }
}
