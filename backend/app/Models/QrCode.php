<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'advantage_id',
        'code',
        'qr_image_url',
        'valid_from',
        'valid_until',
        'status',
        'used_at',
        'validated_by',
        'validation_location_latitude',
        'validation_location_longitude',
        'original_amount',
        'discount_amount',
        'final_amount',
        'signature',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'used_at' => 'datetime',
        'validation_location_latitude' => 'decimal:8',
        'validation_location_longitude' => 'decimal:8',
        'original_amount' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'final_amount' => 'decimal:3',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the advantage
     */
    public function advantage(): BelongsTo
    {
        return $this->belongsTo(Advantage::class);
    }

    /**
     * Get the validator (merchant employee)
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Check if QR code is valid
     */
    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->valid_from <= now()
            && $this->valid_until >= now();
    }

    /**
     * Check if QR code is expired
     */
    public function isExpired(): bool
    {
        return now() > $this->valid_until;
    }

    /**
     * Check if QR code has been used
     */
    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    /**
     * Mark as used
     */
    public function markAsUsed(User $validator, ?float $latitude = null, ?float $longitude = null): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'validated_by' => $validator->id,
            'validation_location_latitude' => $latitude,
            'validation_location_longitude' => $longitude,
        ]);
    }

    /**
     * Cancel QR code
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Verify signature
     */
    public function verifySignature(string $secret): bool
    {
        $dataToSign = $this->user_id . $this->advantage_id . $this->code . $this->created_at->timestamp;
        $expectedSignature = hash_hmac('sha256', $dataToSign, $secret);

        return hash_equals($expectedSignature, $this->signature);
    }

    /**
     * Scope to get active QR codes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    /**
     * Scope to get expired QR codes
     */
    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now())
            ->where('status', 'active');
    }
}
