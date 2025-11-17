<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User wallets
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 3)->default(0); // TND
            $table->decimal('pending_balance', 10, 3)->default(0); // Pending transactions
            $table->decimal('lifetime_earnings', 10, 3)->default(0);
            $table->decimal('lifetime_spent', 10, 3)->default(0);
            $table->string('currency', 3)->default('TND');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // Payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // PAY-XXXXXXXXXX
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->string('payment_type'); // 'booking_deposit', 'advantage_purchase', 'wallet_topup', 'subscription'
            $table->string('payment_provider'); // 'd17', 'flouci', 'paymee', 'wallet'
            $table->decimal('amount', 10, 3);
            $table->decimal('fee', 10, 3)->default(0); // Platform fee
            $table->decimal('merchant_amount', 10, 3)->default(0); // Amount to merchant after commission
            $table->decimal('platform_commission', 10, 3)->default(0);
            $table->string('currency', 3)->default('TND');
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'
            $table->string('provider_transaction_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional payment data
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('merchant_id');
            $table->index('payment_number');
            $table->index('status');
            $table->index('created_at');
        });

        // Wallet transactions
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type'); // 'credit', 'debit', 'refund', 'commission', 'cashback'
            $table->decimal('amount', 10, 3);
            $table->decimal('balance_before', 10, 3);
            $table->decimal('balance_after', 10, 3);
            $table->string('reference_type')->nullable(); // Payment, Booking, Commission, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('user_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });

        // Commissions
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->string('commission_type'); // 'transaction', 'booking', 'subscription', 'boost'
            $table->decimal('transaction_amount', 10, 3);
            $table->decimal('commission_rate', 5, 2); // Percentage
            $table->decimal('commission_amount', 10, 3);
            $table->string('status'); // 'pending', 'approved', 'paid', 'disputed'
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('status');
            $table->index('created_at');
        });

        // Merchant payouts
        Schema::create('merchant_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('payout_number')->unique(); // PAYOUT-XXXXXXXXXX
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 3);
            $table->decimal('commission_deducted', 10, 3)->default(0);
            $table->decimal('net_amount', 10, 3);
            $table->string('method'); // 'bank_transfer', 'wallet', 'check'
            $table->string('status'); // 'pending', 'processing', 'completed', 'failed'
            $table->json('bank_details')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('status');
            $table->index('payout_number');
        });

        // Add payment/commission fields to merchants
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(15.00)->after('auto_confirm_bookings'); // Default 15%
            $table->decimal('pending_balance', 10, 3)->default(0)->after('commission_rate');
            $table->decimal('available_balance', 10, 3)->default(0)->after('pending_balance');
            $table->decimal('lifetime_earnings', 10, 3)->default(0)->after('available_balance');
            $table->decimal('lifetime_commissions_paid', 10, 3)->default(0)->after('lifetime_earnings');
            $table->string('bank_name')->nullable()->after('lifetime_commissions_paid');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_account_number');
            $table->string('iban')->nullable()->after('bank_account_holder');
            $table->boolean('auto_payout_enabled')->default(false)->after('iban');
            $table->decimal('auto_payout_threshold', 10, 3)->default(500.000)->after('auto_payout_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'commission_rate',
                'pending_balance',
                'available_balance',
                'lifetime_earnings',
                'lifetime_commissions_paid',
                'bank_name',
                'bank_account_number',
                'bank_account_holder',
                'iban',
                'auto_payout_enabled',
                'auto_payout_threshold',
            ]);
        });

        Schema::dropIfExists('merchant_payouts');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('wallets');
    }
};
