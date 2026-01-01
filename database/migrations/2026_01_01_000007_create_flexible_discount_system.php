<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create Flexible Discount Rules System
 * 
 * Solves: Current structure only supports percentage discount
 * 
 * Design Decisions:
 * - Supports multiple discount types: percentage, fixed_amount, buy_x_get_y, bundle
 * - discount_rules is the main definition table
 * - discount_conditions defines when discount applies
 * - discount_applications tracks which discounts were applied to orders
 * - Stackable flag determines if discount can combine with others
 */
return new class extends Migration
{
    public function up(): void
    {
        // Main discount rules table
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Internal code or promo code');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type', 30)
                ->comment('percentage, fixed_amount, buy_x_get_y, free_item, bundle_price');
            
            // Value fields based on type
            $table->decimal('discount_value', 15, 2)->default(0)
                ->comment('Percentage or fixed amount');
            $table->unsignedInteger('buy_quantity')->nullable()
                ->comment('For buy_x_get_y: buy this many');
            $table->unsignedInteger('get_quantity')->nullable()
                ->comment('For buy_x_get_y: get this many free/discounted');
            $table->decimal('get_discount_percent', 5, 2)->nullable()
                ->comment('For buy_x_get_y: discount on the get items (100 = free)');
            
            // Limits
            $table->decimal('min_order_amount', 15, 2)->nullable()
                ->comment('Minimum order value to qualify');
            $table->unsignedInteger('min_quantity')->nullable()
                ->comment('Minimum quantity to qualify');
            $table->decimal('max_discount_amount', 15, 2)->nullable()
                ->comment('Cap on discount value');
            $table->unsignedInteger('usage_limit')->nullable()
                ->comment('Total times this can be used');
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('per_user_limit')->nullable()
                ->comment('Times per user');
            
            // Targeting
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('loyalty_tier_ids')->nullable()
                ->comment('Which tiers can use this');
            $table->json('user_ids')->nullable()
                ->comment('Specific users (for exclusive deals)');
            
            // Validity
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_stackable')->default(false)
                ->comment('Can combine with other discounts');
            $table->unsignedSmallInteger('priority')->default(100)
                ->comment('Higher priority applied first');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['is_active', 'valid_from', 'valid_until']);
            $table->index('discount_type');
            $table->index('priority');
        });

        // Track which discounts were applied to each order
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discount_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->cascadeOnDelete()
                ->comment('Null if order-level discount');
            $table->string('discount_type', 30);
            $table->string('discount_code', 50)->nullable();
            $table->string('discount_name');
            $table->decimal('original_amount', 15, 2)->comment('Amount before discount');
            $table->decimal('discount_amount', 15, 2);
            $table->decimal('final_amount', 15, 2)->comment('Amount after discount');
            $table->json('calculation_details')->nullable()
                ->comment('Store how discount was calculated');
            $table->timestamps();
            
            $table->index(['order_id', 'discount_rule_id']);
        });

        // Modify orders table to track discount breakdown
        Schema::table('orders', function (Blueprint $table) {
            // Change discount_amount to be the sum of all discounts
            // Add field for discount breakdown summary
            $table->json('discount_breakdown')->nullable()->after('discount_percent')
                ->comment('JSON summary of all discounts applied');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('discount_breakdown');
        });

        Schema::dropIfExists('order_discounts');
        Schema::dropIfExists('discount_rules');
    }
};
