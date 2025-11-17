<?php

namespace App\Listeners;

use App\Events\QRCodeValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateUserLevel implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(QRCodeValidated $event): void
    {
        try {
            $user = $event->qrCode->user;

            // Count total QR codes used
            $totalUsed = $user->qrCodes()->where('status', 'used')->count();

            // Determine new level
            $newLevel = $this->determineLevel($totalUsed);

            // Update if level changed
            if ($user->level !== $newLevel) {
                $oldLevel = $user->level;
                $user->update(['level' => $newLevel]);

                Log::info('User level updated', [
                    'user_id' => $user->id,
                    'old_level' => $oldLevel,
                    'new_level' => $newLevel,
                    'total_used' => $totalUsed,
                ]);

                // TODO: Send notification to user about level up
            }
        } catch (\Exception $e) {
            Log::error('Failed to update user level', [
                'error' => $e->getMessage(),
                'user_id' => $event->qrCode->user_id,
            ]);
        }
    }

    /**
     * Determine user level based on QR codes used
     */
    protected function determineLevel(int $totalUsed): string
    {
        return match(true) {
            $totalUsed >= 50 => 'platinum',
            $totalUsed >= 25 => 'gold',
            $totalUsed >= 10 => 'silver',
            default => 'bronze',
        };
    }
}
