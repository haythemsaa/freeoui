<?php

namespace App\Services;

use App\Models\Advantage;
use App\Models\QrCode;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Generate QR code for an advantage
     */
    public function generateQRCode(User $user, Advantage $advantage): QrCode
    {
        // Check if advantage is available
        if (!$advantage->isAvailableNow()) {
            throw new \Exception('Cette offre n\'est pas disponible actuellement');
        }

        // Check if user can use this advantage
        if (!$advantage->canBeUsedBy($user)) {
            throw new \Exception('Vous avez déjà utilisé cette offre ou elle n\'est plus disponible');
        }

        // Cancel any existing active QR code for this advantage
        QrCode::where('user_id', $user->id)
            ->where('advantage_id', $advantage->id)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        // Generate unique code
        $code = $this->generateUniqueCode();

        // Create signature
        $signature = $this->createSignature($user->id, $advantage->id, $code);

        // Create QR code
        $qrCode = QrCode::create([
            'user_id' => $user->id,
            'advantage_id' => $advantage->id,
            'code' => $code,
            'valid_from' => now(),
            'valid_until' => now()->addHours(24), // Valid for 24 hours
            'status' => 'active',
            'signature' => $signature,
        ]);

        // Increment QR codes generated counter
        $advantage->increment('qr_codes_generated');

        return $qrCode;
    }

    /**
     * Validate and use a QR code
     */
    public function validateQRCode(
        string $code,
        User $validator,
        ?float $originalAmount = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($code, $validator, $originalAmount, $latitude, $longitude, $notes) {
            // Find QR code
            $qrCode = QrCode::where('code', $code)->firstOrFail();

            // Verify signature
            if (!$this->verifySignature($qrCode)) {
                throw new \Exception('QR code invalide - signature incorrecte');
            }

            // Check if already used
            if ($qrCode->isUsed()) {
                throw new \Exception('Ce QR code a déjà été utilisé le ' . $qrCode->used_at->format('d/m/Y à H:i'));
            }

            // Check if expired
            if ($qrCode->isExpired()) {
                $qrCode->update(['status' => 'expired']);
                throw new \Exception('Ce QR code a expiré');
            }

            // Check if still valid
            if (!$qrCode->isValid()) {
                throw new \Exception('Ce QR code n\'est pas valide');
            }

            $advantage = $qrCode->advantage;
            $user = $qrCode->user;

            // Verify merchant
            if (!$validator->merchant || $validator->merchant->id !== $advantage->merchant_id) {
                throw new \Exception('Vous n\'êtes pas autorisé à valider ce QR code');
            }

            // Check if advantage is still available
            if (!$advantage->isAvailableNow()) {
                throw new \Exception('Cette offre n\'est plus disponible');
            }

            // Calculate amounts
            $originalAmount = $originalAmount ?? 0;
            $discountAmount = 0;
            $finalAmount = $originalAmount;

            if ($originalAmount > 0) {
                $finalAmount = $advantage->calculateFinalAmount($originalAmount);
                $discountAmount = $originalAmount - $finalAmount;
            }

            // Calculate points earned (1 point per TND saved)
            $pointsEarned = (int) $discountAmount;

            // Mark QR code as used
            $qrCode->markAsUsed($validator, $latitude, $longitude);
            $qrCode->update([
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
            ]);

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'merchant_id' => $advantage->merchant_id,
                'advantage_id' => $advantage->id,
                'qr_code_id' => $qrCode->id,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'currency' => 'TND',
                'transaction_date' => now(),
                'validated_by' => $validator->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'points_earned' => $pointsEarned,
                'notes' => $notes,
            ]);

            // Update advantage usage
            $advantage->incrementUsesCount();

            // Add points to user
            if ($pointsEarned > 0) {
                $user->addPoints($pointsEarned);
            }

            // Update savings
            $user->increment('total_savings_tnd', $discountAmount);

            // Update proximity alert if exists
            $this->updateProximityAlert($user, $advantage);

            return [
                'transaction' => $transaction,
                'qr_code' => $qrCode,
                'user' => $user->fresh(),
                'amounts' => [
                    'original' => $originalAmount,
                    'discount' => $discountAmount,
                    'final' => $finalAmount,
                    'currency' => 'TND',
                ],
                'points_earned' => $pointsEarned,
            ];
        });
    }

    /**
     * Cancel a QR code
     */
    public function cancelQRCode(QrCode $qrCode, User $user): void
    {
        if ($qrCode->user_id !== $user->id) {
            throw new \Exception('Vous n\'êtes pas autorisé à annuler ce QR code');
        }

        if ($qrCode->isUsed()) {
            throw new \Exception('Ce QR code a déjà été utilisé et ne peut pas être annulé');
        }

        $qrCode->cancel();
    }

    /**
     * Generate unique code
     */
    protected function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(20));
            // Format: ABCD-EFGH-IJKL-MNOP
            $code = implode('-', str_split($code, 4));
        } while (QrCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create HMAC signature for QR code
     */
    protected function createSignature(string $userId, string $advantageId, string $code): string
    {
        $dataToSign = $userId . $advantageId . $code . now()->timestamp;
        return hash_hmac('sha256', $dataToSign, config('app.qr_secret'));
    }

    /**
     * Verify QR code signature
     */
    protected function verifySignature(QrCode $qrCode): bool
    {
        $dataToSign = $qrCode->user_id . $qrCode->advantage_id . $qrCode->code . $qrCode->created_at->timestamp;
        $expectedSignature = hash_hmac('sha256', $dataToSign, config('app.qr_secret'));

        return hash_equals($expectedSignature, $qrCode->signature);
    }

    /**
     * Update proximity alert log if exists
     */
    protected function updateProximityAlert(User $user, Advantage $advantage): void
    {
        $alert = $user->proximityAlerts()
            ->where('advantage_id', $advantage->id)
            ->latest()
            ->first();

        if ($alert) {
            $alert->update([
                'qr_code_generated' => true,
                'advantage_used' => true,
            ]);
        }
    }

    /**
     * Clean up expired QR codes
     */
    public function cleanupExpiredQRCodes(): int
    {
        return QrCode::expired()->update(['status' => 'expired']);
    }
}
