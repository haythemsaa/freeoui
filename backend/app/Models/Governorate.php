<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Governorate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name_fr',
        'name_ar',
        'code',
        'latitude',
        'longitude',
        'population',
        'area_km2',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'population' => 'integer',
        'area_km2' => 'decimal:2',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
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
