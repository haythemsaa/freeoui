<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

trait HasDateScopes
{
    public function scopeToday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, Carbon::today());
    }

    public function scopeYesterday(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereDate($column, Carbon::yesterday());
    }

    public function scopeThisWeek(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereBetween($column, [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query, string $column = 'created_at'): Builder
    {
        return $query->whereMonth($column, Carbon::now()->month)
            ->whereYear($column, Carbon::now()->year);
    }

    public function scopeLastDays(Builder $query, int $days, string $column = 'created_at'): Builder
    {
        return $query->where($column, '>=', Carbon::now()->subDays($days));
    }

    public function scopeBetweenDates(
        Builder $query,
        Carbon|string $start,
        Carbon|string $end,
        string $column = 'created_at'
    ): Builder {
        return $query->whereBetween($column, [
            $start instanceof Carbon ? $start : Carbon::parse($start),
            $end instanceof Carbon ? $end : Carbon::parse($end),
        ]);
    }

    public function scopeExpired(Builder $query, string $column = 'expires_at'): Builder
    {
        return $query->where($column, '<', Carbon::now());
    }

    public function scopeNotExpired(Builder $query, string $column = 'expires_at'): Builder
    {
        return $query->where($column, '>', Carbon::now());
    }
}
