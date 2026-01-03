<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('price_source')->nullable()->after('unit_price');
            $table->decimal('original_unit_price', 15, 2)->nullable()->after('price_source');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('original_unit_price');
            $table->json('pricing_meta')->nullable()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['price_source', 'original_unit_price', 'discount_percent', 'pricing_meta']);
        });
    }
};
