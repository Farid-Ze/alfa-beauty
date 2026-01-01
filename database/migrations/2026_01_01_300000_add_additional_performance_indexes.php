<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes for frequently queried columns.
 * 
 * This migration adds indexes to optimize:
 * - Product name searches (is_active, name)
 * - Order lookups by user and status
 * - User lookup by email
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add index for product name search optimization
        Schema::table('products', function (Blueprint $table) {
            // Index for search queries: WHERE is_active = true AND name LIKE 'x%'
            $table->index(['is_active', 'name'], 'idx_products_search_name');
        });

        // Add index for order queries by user
        Schema::table('orders', function (Blueprint $table) {
            // Index for user order history: WHERE user_id = x ORDER BY created_at
            $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
            
            // Index for filtering by status
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
        });

        // Add index for cart items cleanup queries
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('created_at', 'idx_cart_items_created');
        });

        // Add index for point transactions by user
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_point_transactions_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_search_name');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_user_created');
            $table->dropIndex('idx_orders_user_status');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_created');
        });

        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_point_transactions_user');
        });
    }
};
