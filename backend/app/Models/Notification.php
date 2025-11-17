<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_type',
        'user_id',
        'title',
        'message',
        'category',
        'priority',
        'data',
        'action_url',
        'image_url',
        'icon',
        'is_sent',
        'is_read',
        'is_clicked',
        'sent_at',
        'read_at',
        'clicked_at',
        'expires_at',
        'delivery_status',
    ];

    protected $casts = [
        'data' => 'array',
        'delivery_status' => 'array',
        'is_sent' => 'boolean',
        'is_read' => 'boolean',
        'is_clicked' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'clicked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_sent', false);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function markAsSent(array $deliveryStatus = []): void
    {
        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
            'delivery_status' => $deliveryStatus,
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAsClicked(): void
    {
        $this->update([
            'is_clicked' => true,
            'clicked_at' => now(),
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
