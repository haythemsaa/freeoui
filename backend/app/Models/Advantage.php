<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Advantage extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'title',
        'short_description',
        'description',
        'type',
        'discount_percentage',
        'discount_amount',
        'buy_quantity',
        'get_quantity',
        'free_item_description',
        'category_id',
        'subcategory_id',
        'minimum_purchase_amount',
        'maximum_discount_amount',
        'terms_and_conditions',
        'exclusions',
        'start_date',
        'end_date',
        'days_available',
        'time_slots',
        'max_uses_per_user',
        'max_uses_total',
        'current_uses_count',
        'main_image_url',
        'images',
        'status',
        'is_featured',
        'is_exclusive',
        'views_count',
        'saves_count',
        'shares_count',
        'proximity_alerts_sent',
        'proximity_alerts_opened',
        'qr_codes_generated',
        'uses_count',
        'conversion_rate',
        'average_rating',
        'reviews_count',
        'created_by',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:3',
        'buy_quantity' => 'integer',
        'get_quantity' => 'integer',
        'minimum_purchase_amount' => 'decimal:3',
        'maximum_discount_amount' => 'decimal:3',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'days_available' => 'array',
        'time_slots' => 'json',
        'max_uses_per_user' => 'integer',
        'max_uses_total' => 'integer',
        'current_uses_count' => 'integer',
        'images' => 'array',
        'is_featured' => 'boolean',
        'is_exclusive' => 'boolean',
        'views_count' => 'integer',
        'saves_count' => 'integer',
        'shares_count' => 'integer',
        'proximity_alerts_sent' => 'integer',
        'proximity_alerts_opened' => 'integer',
        'qr_codes_generated' => 'integer',
        'uses_count' => 'integer',
        'conversion_rate' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'reviews_count' => 'integer',
    ];

    /**
     * Get the merchant that owns the advantage
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the subcategory
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the advantage's proximity alerts
     */
    public function proximityAlerts(): HasMany
    {
        return $this->hasMany(ProximityAlertLog::class);
    }

    /**
     * Get the advantage's QR codes
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * Get the advantage's transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the advantage's reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Check if advantage is currently active
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date <= now()
            && $this->end_date >= now();
    }

    /**
     * Check if advantage is available today
     */
    public function isAvailableToday(): bool
    {
        $dayOfWeek = now()->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)
        return in_array($dayOfWeek, $this->days_available ?? []);
    }

    /**
     * Check if advantage is available now
     */
    public function isAvailableNow(): bool
    {
        if (!$this->isActive() || !$this->isAvailableToday()) {
            return false;
        }

        if (!$this->time_slots || empty($this->time_slots)) {
            return true; // Available all day
        }

        $currentTime = now()->format('H:i');

        foreach ($this->time_slots as $slot) {
            if ($currentTime >= $slot['start'] && $currentTime <= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user can still use this advantage
     */
    public function canBeUsedBy(User $user): bool
    {
        if (!$this->isAvailableNow()) {
            return false;
        }

        // Check max uses per user
        if ($this->max_uses_per_user) {
            $userUsesCount = $this->transactions()
                ->where('user_id', $user->id)
                ->count();

            if ($userUsesCount >= $this->max_uses_per_user) {
                return false;
            }
        }

        // Check total max uses
        if ($this->max_uses_total && $this->current_uses_count >= $this->max_uses_total) {
            return false;
        }

        return true;
    }

    /**
     * Increment view counter
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment saves counter
     */
    public function incrementSaves(): void
    {
        $this->increment('saves_count');
    }

    /**
     * Increment shares counter
     */
    public function incrementShares(): void
    {
        $this->increment('shares_count');
    }

    /**
     * Increment proximity alerts sent
     */
    public function incrementProximityAlertsSent(): void
    {
        $this->increment('proximity_alerts_sent');
        $this->merchant->incrementAlertsSent();
    }

    /**
     * Increment proximity alerts opened
     */
    public function incrementProximityAlertsOpened(): void
    {
        $this->increment('proximity_alerts_opened');
        $this->updateConversionRate();
    }

    /**
     * Increment uses count
     */
    public function incrementUsesCount(): void
    {
        $this->increment('uses_count');
        $this->increment('current_uses_count');
        $this->updateConversionRate();
    }

    /**
     * Update conversion rate
     */
    protected function updateConversionRate(): void
    {
        if ($this->proximity_alerts_sent > 0) {
            $rate = ($this->uses_count / $this->proximity_alerts_sent) * 100;
            $this->update(['conversion_rate' => round($rate, 2)]);
        }
    }

    /**
     * Get discount display text
     */
    public function getDiscountDisplay(): string
    {
        return match ($this->type) {
            'percentage' => "-{$this->discount_percentage}%",
            'fixed_amount' => "-{$this->discount_amount} TND",
            'buy_x_get_y' => "Achetez {$this->buy_quantity}, obtenez {$this->get_quantity}",
            'free_item' => $this->free_item_description,
            default => 'Offre spéciale',
        };
    }

    /**
     * Calculate final amount after discount
     */
    public function calculateFinalAmount(float $originalAmount): float
    {
        $discount = match ($this->type) {
            'percentage' => $originalAmount * ($this->discount_percentage / 100),
            'fixed_amount' => $this->discount_amount,
            default => 0,
        };

        // Apply maximum discount limit if set
        if ($this->maximum_discount_amount) {
            $discount = min($discount, $this->maximum_discount_amount);
        }

        return max(0, $originalAmount - $discount);
    }

    /**
     * Scope to get active advantages
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope to get featured advantages
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get advantages by category
     */
    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to get advantages expiring soon
     */
    public function scopeExpiringSoon($query, int $hours = 24)
    {
        return $query->where('end_date', '<=', now()->addHours($hours))
            ->where('end_date', '>', now());
    }
}
