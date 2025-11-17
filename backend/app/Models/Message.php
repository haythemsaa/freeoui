<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'message_type',
        'attachments',
        'is_read',
        'read_at',
        'is_edited',
        'edited_at',
        'metadata',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_read' => 'boolean',
        'is_edited' => 'boolean',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeText($query)
    {
        return $query->where('message_type', 'text');
    }

    public function scopeSystem($query)
    {
        return $query->where('message_type', 'system');
    }

    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function edit(string $newMessage): void
    {
        $this->update([
            'message' => $newMessage,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }
}
