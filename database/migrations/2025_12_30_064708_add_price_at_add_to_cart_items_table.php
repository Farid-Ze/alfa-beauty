<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tracks price at time of adding to cart for price change detection.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Store price when item was added for price sync verification
            $table->decimal('price_at_add', 15, 2)->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('price_at_add');
        });
    }
};
