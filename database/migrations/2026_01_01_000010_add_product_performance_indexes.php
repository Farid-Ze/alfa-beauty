<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Indexes for Product Queries
 * 
 * Adds composite indexes to optimize common query patterns:
 * - Product listing with brand/category filters
 * - Stock availability checks
 * - Active product filtering
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Composite index for brand filtering with active check
            $table->index(['is_active', 'brand_id'], 'idx_products_active_brand');
            
            // Composite index for category filtering with active check
            $table->index(['is_active', 'category_id'], 'idx_products_active_category');
            
            // Composite index for stock availability with active check
            $table->index(['is_active', 'stock'], 'idx_products_active_stock');
            
            // Index for featured products (home page)
            $table->index(['is_active', 'is_featured'], 'idx_products_active_featured');
            
            // Index for price range filtering
            $table->index(['is_active', 'base_price'], 'idx_products_active_price');
        });
        
        // Index for order items lookups
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_id'], 'idx_order_items_order_product');
        });
        
        // Index for cart items lookups
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['cart_id', 'product_id'], 'idx_cart_items_cart_product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_active_brand');
            $table->dropIndex('idx_products_active_category');
            $table->dropIndex('idx_products_active_stock');
            $table->dropIndex('idx_products_active_featured');
            $table->dropIndex('idx_products_active_price');
        });
        
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_product');
        });
        
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_cart_product');
        });
    }
};
