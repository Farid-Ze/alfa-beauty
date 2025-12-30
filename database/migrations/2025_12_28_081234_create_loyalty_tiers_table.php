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
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Guest, Silver, Gold
            $table->string('slug')->unique();
            $table->decimal('min_spend', 15, 2)->default(0); // Minimum annual spend
            $table->decimal('discount_percent', 5, 2)->default(0); // Tier discount %
            $table->decimal('point_multiplier', 3, 2)->default(1); // Points earning multiplier
            $table->boolean('free_shipping')->default(false);
            $table->string('badge_color')->nullable(); // #C9A962 for Gold
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
