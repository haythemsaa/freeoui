<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'lifetime_earnings',
        'lifetime_spent',
        'currency',
        'is_active',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'decimal:3',
        'pending_balance' => 'decimal:3',
        'lifetime_earnings' => 'decimal:3',
        'lifetime_spent' => 'decimal:3',
        'is_active' => 'boolean',
        'last_transaction_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function credit(float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        $this->increment('lifetime_earnings', $amount);
        $this->update(['last_transaction_at' => now()]);

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'transaction_type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'description' => $description,
        ]);
    }

    public function debit(float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        if (!$this->hasBalance($amount)) {
            throw new \Exception('Insufficient wallet balance');
        }

        $balanceBefore = $this->balance;
        $this->decrement('balance', $amount);
        $this->increment('lifetime_spent', $amount);
        $this->update(['last_transaction_at' => now()]);

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'transaction_type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore - $amount,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'description' => $description,
        ]);
    }

    public function refund(float $amount, string $description, ?Model $reference = null): WalletTransaction
    {
        $balanceBefore = $this->balance;
        $this->increment('balance', $amount);
        $this->update(['last_transaction_at' => now()]);

        return WalletTransaction::create([
            'wallet_id' => $this->id,
            'user_id' => $this->user_id,
            'transaction_type' => 'refund',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference?->id,
            'description' => $description,
        ]);
    }
}
