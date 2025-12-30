<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds batch allocation tracking for BPOM compliance.
     * Each order item stores which batches were used (FEFO).
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // JSON array of batch allocations from FEFO algorithm
            // Format: [{"batch_id": 1, "batch_number": "B001", "quantity": 5, "expires_at": "2025-12-31"}, ...]
            $table->json('batch_allocations')->nullable()->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('batch_allocations');
        });
    }
};
