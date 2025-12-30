<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Customer-specific pricing for B2B customers.
     * Supports pricing at product, brand, or category level.
     */
    public function up(): void
    {
        Schema::create('customer_price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            
            // Pricing options (use one or the other)
            $table->decimal('custom_price', 15, 2)->nullable(); // Fixed price override
            $table->decimal('discount_percent', 5, 2)->nullable(); // Percentage discount
            
            // Quantity requirements
            $table->integer('min_quantity')->default(1);
            
            // Validity period
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            
            // Priority for resolution (higher = takes precedence)
            $table->integer('priority')->default(0);
            
            // Notes
            $table->string('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for fast lookup
            $table->index(['user_id', 'product_id']);
            $table->index(['user_id', 'brand_id']);
            $table->index(['user_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_price_lists');
    }
};
