<?php

namespace App\Services;

use App\Models\User;
use App\Models\Merchant;
use App\Models\Advantage;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\BookingBlackout;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Create a booking
     */
    public function createBooking(
        User $user,
        Merchant $merchant,
        Carbon $bookingDate,
        string $bookingTime,
        int $partySize,
        ?Advantage $advantage = null,
        array $customerInfo = []
    ): Booking {
        // Validate merchant accepts bookings
        if (!$merchant->accepts_bookings) {
            throw new \Exception('This merchant does not accept bookings');
        }

        // Validate booking is within allowed timeframe
        $minDate = now()->addHours($merchant->booking_advance_hours);
        $maxDate = now()->addDays($merchant->booking_max_days);

        if ($bookingDate->lt($minDate)) {
            throw new \Exception("Bookings must be at least {$merchant->booking_advance_hours} hours in advance");
        }

        if ($bookingDate->gt($maxDate)) {
            throw new \Exception("Bookings cannot be more than {$merchant->booking_max_days} days in advance");
        }

        // Check availability
        if (!$this->isTimeSlotAvailable($merchant, $bookingDate, $bookingTime, $partySize)) {
            throw new \Exception('This time slot is not available');
        }

        return DB::transaction(function () use ($user, $merchant, $bookingDate, $bookingTime, $partySize, $advantage, $customerInfo) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'advantage_id' => $advantage?->id,
                'booking_date' => $bookingDate,
                'booking_time' => $bookingTime,
                'party_size' => $partySize,
                'status' => $merchant->requires_confirmation ? 'pending' : 'confirmed',
                'customer_name' => $customerInfo['name'] ?? $user->name,
                'customer_phone' => $customerInfo['phone'] ?? $user->phone,
                'customer_email' => $customerInfo['email'] ?? $user->email,
                'special_requests' => $customerInfo['special_requests'] ?? null,
                'confirmed_at' => $merchant->requires_confirmation ? null : now(),
            ]);

            // Award points for booking
            if (app()->has(LoyaltyService::class)) {
                app(LoyaltyService::class)->awardPoints(
                    $user,
                    100,
                    'Booking created',
                    $booking,
                    now()->addYear()
                );
            }

            return $booking;
        });
    }

    /**
     * Check if time slot is available
     */
    public function isTimeSlotAvailable(
        Merchant $merchant,
        Carbon $date,
        string $time,
        int $partySize
    ): bool {
        // Check if date/time is in the past
        $bookingDateTime = Carbon::parse($date->toDateString() . ' ' . $time);
        if ($bookingDateTime->isPast()) {
            return false;
        }

        // Check blackout dates
        $isBlackedOut = BookingBlackout::where('merchant_id', $merchant->id)
            ->forDate($date)
            ->get()
            ->contains(function ($blackout) use ($date, $time) {
                return $blackout->isActiveForDateTime($date->toDateString(), $time);
            });

        if ($isBlackedOut) {
            return false;
        }

        // Get booking slot for this day of week
        $dayOfWeek = strtolower($date->englishDayOfWeek);
        $slot = BookingSlot::where('merchant_id', $merchant->id)
            ->forDay($dayOfWeek)
            ->active()
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->first();

        if (!$slot) {
            return false;
        }

        // Check capacity
        $bookedCapacity = Booking::where('merchant_id', $merchant->id)
            ->where('booking_date', $date)
            ->where('booking_time', $time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('party_size');

        return ($bookedCapacity + $partySize) <= $slot->max_capacity;
    }

    /**
     * Get available time slots for a date
     */
    public function getAvailableSlots(Merchant $merchant, Carbon $date): array
    {
        $dayOfWeek = strtolower($date->englishDayOfWeek);

        $slots = BookingSlot::where('merchant_id', $merchant->id)
            ->forDay($dayOfWeek)
            ->active()
            ->get();

        $availableSlots = [];

        foreach ($slots as $slot) {
            $slotTimes = $this->generateTimeSlots($slot, $date);
            $availableSlots = array_merge($availableSlots, $slotTimes);
        }

        return $availableSlots;
    }

    /**
     * Generate time slots from booking slot configuration
     */
    protected function generateTimeSlots(BookingSlot $slot, Carbon $date): array
    {
        $times = [];
        $start = Carbon::parse($slot->start_time);
        $end = Carbon::parse($slot->end_time);

        while ($start->lt($end)) {
            $timeString = $start->format('H:i:s');

            // Check blackouts
            $isBlackedOut = BookingBlackout::where('merchant_id', $slot->merchant_id)
                ->forDate($date)
                ->get()
                ->contains(function ($blackout) use ($date, $timeString) {
                    return $blackout->isActiveForDateTime($date->toDateString(), $timeString);
                });

            if (!$isBlackedOut) {
                // Calculate available capacity
                $bookedCapacity = Booking::where('merchant_id', $slot->merchant_id)
                    ->where('booking_date', $date)
                    ->where('booking_time', $timeString)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->sum('party_size');

                $availableCapacity = $slot->max_capacity - $bookedCapacity;

                if ($availableCapacity > 0) {
                    $times[] = [
                        'time' => $timeString,
                        'display_time' => $start->format('H:i'),
                        'available_capacity' => $availableCapacity,
                    ];
                }
            }

            $start->addMinutes($slot->duration_minutes);
        }

        return $times;
    }

    /**
     * Confirm a booking
     */
    public function confirmBooking(Booking $booking): Booking
    {
        if (!$booking->isPending()) {
            throw new \Exception('Only pending bookings can be confirmed');
        }

        $booking->confirm();

        // TODO: Send confirmation notification

        return $booking;
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): Booking
    {
        if (!$booking->canCancel()) {
            throw new \Exception('This booking cannot be cancelled');
        }

        $booking->cancel($reason);

        // TODO: Send cancellation notification

        return $booking;
    }

    /**
     * Mark booking as completed and award points
     */
    public function completeBooking(Booking $booking): Booking
    {
        $booking->markAsCompleted();

        // Award bonus points for completed booking
        if (app()->has(LoyaltyService::class)) {
            app(LoyaltyService::class)->awardBonusPoints(
                $booking->user,
                50,
                'Completed booking'
            );
        }

        return $booking;
    }

    /**
     * Send booking reminders
     */
    public function sendReminders(): int
    {
        // Send reminders 24 hours before booking
        $bookings = Booking::confirmed()
            ->whereNull('reminder_sent_at')
            ->where('booking_date', '>=', now())
            ->where('booking_date', '<=', now()->addDay())
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $booking->sendReminder();
            $count++;
        }

        return $count;
    }

    /**
     * Process no-shows
     */
    public function processNoShows(): int
    {
        // Mark as no-show if 1 hour past booking time
        $bookings = Booking::confirmed()
            ->where('booking_date', '<', now()->subHour()->toDateString())
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            $bookingDateTime = Carbon::parse($booking->booking_date->toDateString() . ' ' . $booking->booking_time);

            if ($bookingDateTime->addHour()->isPast()) {
                $booking->markAsNoShow();
                $count++;
            }
        }

        return $count;
    }
}
