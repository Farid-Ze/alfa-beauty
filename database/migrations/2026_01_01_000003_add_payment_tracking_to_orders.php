<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Partial Payment Tracking to Orders
 * 
 * Solves: payment_status 'partially_paid' exists but no way to track amounts
 * 
 * Design Decisions:
 * - amount_paid tracks total received payments
 * - balance_due is computed but cached for query performance
 * - due_date for payment term tracking
 * - payment_term_days allows flexible net terms (NET30, NET60, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('payment_status')
                ->comment('Total amount paid so far');
            $table->decimal('balance_due', 15, 2)->default(0)->after('amount_paid')
                ->comment('Remaining balance (total_amount - amount_paid)');
            $table->unsignedSmallInteger('payment_term_days')->default(0)->after('balance_due')
                ->comment('Payment term in days (0=immediate, 30=NET30, etc.)');
            $table->date('payment_due_date')->nullable()->after('payment_term_days')
                ->comment('When payment is due');
            $table->timestamp('last_payment_date')->nullable()->after('payment_due_date')
                ->comment('Date of most recent payment');
            
            $table->index(['payment_status', 'payment_due_date']);
            $table->index('balance_due');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status', 'payment_due_date']);
            $table->dropIndex(['balance_due']);
            $table->dropColumn([
                'amount_paid',
                'balance_due',
                'payment_term_days',
                'payment_due_date',
                'last_payment_date',
            ]);
        });
    }
};
