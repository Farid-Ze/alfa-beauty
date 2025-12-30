<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Volume-based tiered pricing for products.
     * Buy more, pay less per unit.
     */
    public function up(): void
    {
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            // Quantity range
            $table->integer('min_quantity');
            $table->integer('max_quantity')->nullable(); // NULL = no upper limit
            
            // Pricing (use one)
            $table->decimal('unit_price', 15, 2)->nullable(); // Fixed unit price at this tier
            $table->decimal('discount_percent', 5, 2)->nullable(); // Or percentage off base price
            
            $table->timestamps();
            
            // Index for fast lookup
            $table->index(['product_id', 'min_quantity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
