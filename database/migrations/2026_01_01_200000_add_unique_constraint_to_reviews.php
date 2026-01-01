<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Add Unique Constraint to Reviews
 * 
 * This migration ensures that a user can only submit one review per product,
 * preventing duplicate reviews from race conditions (double-click, etc.)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop the existing index first (if exists) since we're replacing it with unique
            $table->dropIndex(['user_id', 'product_id']);
            
            // Add unique constraint to prevent duplicate reviews
            $table->unique(['user_id', 'product_id'], 'reviews_user_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_user_product_unique');
            
            // Restore the regular index
            $table->index(['user_id', 'product_id']);
        });
    }
};
