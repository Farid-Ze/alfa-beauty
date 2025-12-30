<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds discount tracking columns to orders table for proper
     * accounting records and tier discount visibility.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->nullable()->after('total_amount');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal');
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_amount', 'discount_percent']);
        });
    }
};
