<?php

namespace App\Services;

use App\Models\User;
use App\Models\GiftCard;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GiftCardService
{
    public function __construct(
        private NotificationService $notificationService,
        private PaymentService $paymentService
    ) {}

    /**
     * Purchase a gift card
     */
    public function purchaseGiftCard(User $buyer, array $data): GiftCard
    {
        DB::beginTransaction();

        try {
            // Process payment
            $payment = $this->paymentService->createPayment($buyer, [
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'payment_type' => 'gift_card_purchase',
                'currency' => 'TND',
            ]);

            $paymentResult = $this->paymentService->processPayment($payment);

            if ($paymentResult['status'] !== 'completed') {
                throw new \Exception('Payment failed');
            }

            // Generate unique code
            $code = $this->generateUniqueCode();

            // Create gift card
            $giftCard = GiftCard::create([
                'code' => $code,
                'buyer_id' => $buyer->id,
                'recipient_email' => $data['recipient_email'] ?? null,
                'recipient_phone' => $data['recipient_phone'] ?? null,
                'amount' => $data['amount'],
                'balance' => $data['amount'],
                'message' => $data['message'] ?? null,
                'design_template' => $data['design_template'] ?? 'default',
                'status' => 'active',
                'expires_at' => now()->addYear(),
            ]);

            // Send gift card to recipient
            if (isset($data['recipient_email'])) {
                $this->sendGiftCardEmail($giftCard);
            }

            // Notify buyer
            $this->notificationService->send(
                $buyer,
                '🎁 Carte cadeau achetée !',
                "Votre carte cadeau de {$giftCard->amount} TND a été créée avec succès.",
                'gift_card',
                ['gift_card_id' => $giftCard->id, 'code' => $code]
            );

            DB::commit();

            return $giftCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Redeem gift card
     */
    public function redeemGiftCard(User $user, string $code): GiftCard
    {
        $giftCard = GiftCard::where('code', $code)->first();

        if (!$giftCard) {
            throw new \Exception('Carte cadeau invalide');
        }

        if ($giftCard->status !== 'active') {
            throw new \Exception('Cette carte cadeau a déjà été utilisée ou est expirée');
        }

        if ($giftCard->expires_at < now()) {
            $giftCard->update(['status' => 'expired']);
            throw new \Exception('Cette carte cadeau est expirée');
        }

        if ($giftCard->balance <= 0) {
            $giftCard->update(['status' => 'redeemed']);
            throw new \Exception('Cette carte cadeau n\'a plus de solde');
        }

        DB::beginTransaction();

        try {
            // Credit user's wallet with gift card balance
            $user->increment('wallet_balance', $giftCard->balance);

            // Mark as redeemed
            $giftCard->update([
                'redeemed_by_id' => $user->id,
                'redeemed_at' => now(),
                'balance' => 0,
                'status' => 'redeemed',
            ]);

            // Send notifications
            $this->notificationService->send(
                $user,
                '🎉 Carte cadeau activée !',
                "{$giftCard->balance} TND ont été ajoutés à votre portefeuille !",
                'gift_card_redeemed',
                ['amount' => $giftCard->balance],
                'high'
            );

            if ($giftCard->buyer) {
                $this->notificationService->send(
                    $giftCard->buyer,
                    '🎁 Carte cadeau utilisée',
                    "Votre carte cadeau de {$giftCard->amount} TND a été activée par {$user->first_name}",
                    'gift_card_used',
                    ['gift_card_id' => $giftCard->id]
                );
            }

            DB::commit();

            return $giftCard;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check gift card balance
     */
    public function checkBalance(string $code): array
    {
        $giftCard = GiftCard::where('code', $code)->first();

        if (!$giftCard) {
            throw new \Exception('Carte cadeau invalide');
        }

        return [
            'code' => $giftCard->code,
            'balance' => $giftCard->balance,
            'original_amount' => $giftCard->amount,
            'status' => $giftCard->status,
            'expires_at' => $giftCard->expires_at,
            'is_expired' => $giftCard->expires_at < now(),
            'message' => $giftCard->message,
        ];
    }

    /**
     * Get user's purchased gift cards
     */
    public function getPurchasedGiftCards(User $user): \Illuminate\Support\Collection
    {
        return GiftCard::where('buyer_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get user's redeemed gift cards
     */
    public function getRedeemedGiftCards(User $user): \Illuminate\Support\Collection
    {
        return GiftCard::where('redeemed_by_id', $user->id)
            ->orderByDesc('redeemed_at')
            ->get();
    }

    /**
     * Generate unique gift card code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = 'FO-' . strtoupper(Str::random(12));
        } while (GiftCard::where('code', $code)->exists());

        return $code;
    }

    /**
     * Send gift card email
     */
    private function sendGiftCardEmail(GiftCard $giftCard): void
    {
        // TODO: Implement email sending with gift card details
        // This would integrate with EmailService to send a beautifully designed email
    }

    /**
     * Expire old gift cards
     */
    public function expireOldCards(): int
    {
        return GiftCard::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    /**
     * Get gift card statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_issued' => GiftCard::count(),
            'total_value' => GiftCard::sum('amount'),
            'total_redeemed' => GiftCard::where('status', 'redeemed')->count(),
            'total_redeemed_value' => GiftCard::where('status', 'redeemed')->sum('amount'),
            'active_cards' => GiftCard::where('status', 'active')->count(),
            'active_balance' => GiftCard::where('status', 'active')->sum('balance'),
            'this_month_issued' => GiftCard::where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}
