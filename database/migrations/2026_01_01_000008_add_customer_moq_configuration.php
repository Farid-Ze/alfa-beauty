<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Customer-Level MOQ Configuration
 * 
 * Solves: No minimum order per customer/channel type
 * 
 * Design Decisions:
 * - customer_order_settings allows per-customer configuration
 * - MOQ can be by amount (min Rp X) or by quantity
 * - Different customer types (salon, distributor, reseller) can have different MOQ
 * - Override per product or category possible
 */
return new class extends Migration
{
    public function up(): void
    {
        // Customer-level order settings
        Schema::create('customer_order_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Global MOQ for this customer
            $table->decimal('min_order_amount', 15, 2)->nullable()
                ->comment('Minimum order value in IDR');
            $table->unsignedInteger('min_order_units')->nullable()
                ->comment('Minimum total units per order');
            
            // Payment settings
            $table->unsignedSmallInteger('default_payment_term_days')->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->decimal('current_credit_used', 15, 2)->default(0);
            
            // Shipping
            $table->boolean('free_shipping_eligible')->default(false);
            $table->decimal('free_shipping_threshold', 15, 2)->nullable();
            
            // Order settings
            $table->boolean('require_po_number')->default(false);
            $table->boolean('allow_backorder')->default(false);
            $table->boolean('allow_partial_delivery')->default(true);
            
            $table->timestamps();
            
            $table->unique('user_id');
        });

        // Product-level MOQ override per customer type
        Schema::create('product_moq_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Target (one must be set)
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('loyalty_tier_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('customer_type', 30)->nullable()
                ->comment('salon, distributor, reseller, etc.');
            
            // Override values
            $table->unsignedSmallInteger('min_order_qty');
            $table->unsignedSmallInteger('order_increment')->nullable();
            $table->unsignedSmallInteger('max_order_qty')->nullable();
            
            $table->timestamps();
            
            $table->index(['product_id', 'customer_type']);
            $table->index(['product_id', 'loyalty_tier_id']);
        });

        // Add customer_type to users for business segmentation
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_type', 30)->nullable()->after('role')
                ->comment('salon, distributor, reseller, end_customer, etc.');
            $table->string('business_name')->nullable()->after('customer_type');
            $table->string('npwp', 30)->nullable()->after('business_name')
                ->comment('Tax ID for business customers');
            
            $table->index('customer_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['customer_type']);
            $table->dropColumn(['customer_type', 'business_name', 'npwp']);
        });

        Schema::dropIfExists('product_moq_overrides');
        Schema::dropIfExists('customer_order_settings');
    }
};
