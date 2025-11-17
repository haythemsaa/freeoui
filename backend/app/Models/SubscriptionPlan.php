<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name_fr',
        'name_ar',
        'slug',
        'description',
        'price_monthly',
        'price_quarterly',
        'price_yearly',
        'currency',
        'max_active_advantages',
        'max_photos_per_advantage',
        'monthly_alerts_quota',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:3',
        'price_quarterly' => 'decimal:3',
        'price_yearly' => 'decimal:3',
        'max_active_advantages' => 'integer',
        'max_photos_per_advantage' => 'integer',
        'monthly_alerts_quota' => 'integer',
        'features' => 'json',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantSubscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
