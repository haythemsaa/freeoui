<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'governorate_id',
        'name_fr',
        'name_ar',
        'postal_code',
        'latitude',
        'longitude',
        'population',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'population' => 'integer',
    ];

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }
}
