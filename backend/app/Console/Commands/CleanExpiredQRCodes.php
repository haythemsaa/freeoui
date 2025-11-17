<?php

namespace App\Console\Commands;

use App\Models\QRCode;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanExpiredQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'qrcodes:clean-expired
                            {--dry-run : Run without actually deleting}
                            {--days=30 : Number of days to keep expired QR codes}';

    /**
     * The console command description.
     */
    protected $description = 'Clean expired QR codes older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        $this->info("Cleaning expired QR codes older than {$days} days...");

        $cutoffDate = Carbon::now()->subDays($days);

        $query = QRCode::where('status', 'expired')
            ->where('expires_at', '<', $cutoffDate);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No expired QR codes to clean.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} expired QR codes to clean.");

        if ($dryRun) {
            $this->warn('DRY RUN - No QR codes were deleted.');

            // Show sample
            $samples = $query->limit(5)->get();
            $this->table(
                ['ID', 'Code', 'Status', 'Expired At'],
                $samples->map(fn($qr) => [
                    $qr->id,
                    $qr->code,
                    $qr->status,
                    $qr->expires_at->format('Y-m-d H:i:s'),
                ])
            );

            return self::SUCCESS;
        }

        if (!$this->confirm("Are you sure you want to delete {$count} QR codes?")) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Successfully deleted {$deleted} expired QR codes.");

        Log::info('Expired QR codes cleaned', [
            'count' => $deleted,
            'cutoff_date' => $cutoffDate,
        ]);

        return self::SUCCESS;
    }
}
