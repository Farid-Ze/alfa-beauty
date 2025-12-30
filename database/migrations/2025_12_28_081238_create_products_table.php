<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique(); // AFP-SDL-001
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 15, 2); // Base price before discounts
            $table->integer('stock')->default(0);
            $table->text('description')->nullable();
            $table->text('inci_list')->nullable(); // Ingredient list
            $table->text('how_to_use')->nullable();
            $table->boolean('is_halal')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->string('bpom_number')->nullable(); // BPOM Registration
            $table->json('images')->nullable(); // Array of image URLs
            $table->string('video_url')->nullable();
            $table->string('msds_url')->nullable(); // Material Safety Data Sheet
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->index('sku');
            $table->index(['brand_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
