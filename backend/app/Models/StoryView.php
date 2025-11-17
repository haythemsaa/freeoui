<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryView extends Model
{
    protected $fillable = [
        'user_id',
        'story_id',
        'viewed_at',
        'watch_duration',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'watch_duration' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
